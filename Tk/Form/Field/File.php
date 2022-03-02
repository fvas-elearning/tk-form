<?php
namespace Tk\Form\Field;

use Tk\Form;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Handle a single file upload field.
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class File extends Input
{

    /**
     * The max size for this file upload in bytes
     * Default: self::string2Bytes(ini_get('upload_max_filesize'))
     * @var int
     */
    protected $maxBytes = 0;

    /**
     * @var UploadedFile[]
     */
    protected $uploadedFiles = array();

    /**
     * @var bool
     */
    protected $delFile = false;

    /**
     * @var string
     */
    protected $previousValue = '';

    /**
     * The path to save the file relative to the dataPath.
     * @var string
     */
    protected $destPath = '';

    /**
     * The full data path to save the file (EG: Tk.Config::getInstance()->getDataPath()
     * @var string
     */
    protected $dataPath = '';


    /**
     * __construct
     *
     * @param string $name
     * @param string|null $destPath If not set then the file will not be moved and the object will not be set
     * @param string|null $dataPath If not set then the Tk.Config::getDataPath() will be used
     * @throws Form\Exception
     */
    public function __construct($name, $destPath = null, $dataPath = null)
    {
        parent::__construct($name);
        $this->maxBytes = min( \Tk\File::string2Bytes(ini_get('upload_max_filesize')),
            \Tk\File::string2Bytes(ini_get('post_max_size')) );
        $this->setType('file');
        if (!$dataPath) {
            $dataPath = \Tk\Config::getInstance()->getDataPath();
        }
        $this->dataPath = rtrim($dataPath, '/');
        if ($destPath) {
            $destPath = str_replace($dataPath, '', $destPath);
        }
        $this->destPath = rtrim($destPath, '/');

        // Add manually setAttr('multiple, 'multiple)....
//        if ($this->isArrayField()) {
//            $this->setAttr('multiple', 'multiple');
//        }
    }

    /**
     * @param $name
     * @param string|null $destPath
     * @param string|null $dataPath
     * @return static
     * @throws Form\Exception
     */
    public static function create($name, $destPath = null, $dataPath = null)
    {
        return new static($name, $destPath, $dataPath);
    }

    /**
     * Set the form for this element
     *
     * @param Form $form
     * @return $this
     */
    public function setForm(Form $form)
    {
        parent::setForm($form);
        $form->setAttr('enctype', Form::ENCTYPE_MULTIPART);
        
        // load any uploaded files if available
        $request = \Tk\Request::createFromGlobals();
        $this->uploadedFiles = $request->getUploadedFile(str_replace('.', '_', $this->getName()));

        if (!is_array($this->uploadedFiles)) $this->uploadedFiles = array($this->uploadedFiles);
        if (count($this->uploadedFiles) && ($this->uploadedFiles[0] == null || $this->uploadedFiles[0]->getError() == \UPLOAD_ERR_NO_FILE)) {
            $this->uploadedFiles = array();
        }
        
        return $this;
    }

    /**
     * This method does the following:
     * 
     *  o Loads the field value with the relative file path: `$destPath . '/' . $uploadedFile->getClientOriginalName()`
     *  o Uploads the file to the path defined by: `$dataPath . $destPath . '/' . $uploadedFile->getClientOriginalName()`
     * 
     * Override this with your own if you need different functionality.
     * 
     * @param array|\ArrayObject $values
     * @return $this
     */
    public function load($values)
    {
        // Do any functions to update the field value, do not modify the file at this stage
        if ($this->getForm()->isSubmitted() && $this->isValid()) {
            if ($this->hasFile()) {
                $this->previousValue = $this->getValue();
                $newVal = array();
                /** @var UploadedFile $uploadedFile */
                foreach ($this->getUploadedFiles() as $uploadedFile) {
                    $newVal[] = $this->destPath . '/' . $uploadedFile->getClientOriginalName();
                }
                $this->setValue($newVal);
            }
            if (isset($values[$this->getDeleteEventName()])) {
                $this->setValue('');
            }
        } else {
            // load object value if not submitted
            parent::load($values);
        }
        return $this;
    }

    /**
     * Update the physical file state.
     * This should be called after the form data has been validated
     *
     * <code>
     *  ...
     *
     *  // onSubmit callback
     *  $form->addFieldErrors($this->company->validate());
     *  if ($form->hasErrors()) {
     *    return;
     *  }
     *
     *  $form->getField('logo')->saveFile();      // <<--- Here!!!
     *
     *  // resize the image if needed
     *  if ($form->getField('logo')->hasFile()) {
     *    $fullPath = $this->getConfig()->getDataPath() . $this->company->logo;
     *    Tk.Image::create($fullPath)->bestFit(256, 256)->save();
     *  }
     *
     *  $this->company->save();
     *
     *  ...
     * </code>
     * @return int Return the number of files modified
     * @throws Form\Exception
     */
    public function saveFile()
    {
        $cnt = 0;

        if ($this->getForm()->hasErrors()) return $cnt;

        if ($this->hasFile()) {
            // delete any existing files if new files are valid and path writable
            $this->deleteFile($this->previousValue);
            // move new files if valid
            $cnt = $this->moveFile($this->dataPath . $this->destPath);
        } else {
            // Check if the delete file checkbox is checked.
            if (\Tk\Request::createFromGlobals()->has($this->getDeleteEventName())) {
                $this->deleteFile($this->getValue());
            }
        }

        return $cnt;
    }


    /**
     * @param string|array $relFilePath
     * @return int Return the number of files deleted
     */
    public function deleteFile($relFilePath)
    {
        if (!$relFilePath) return 0;
        if (!is_array($relFilePath)) $relFilePath = array($relFilePath);
        $cnt = 0;
        foreach ($relFilePath as $filePath) {
            $fullPath = $this->dataPath . rtrim($filePath, '/');
            if (is_file($fullPath)) {
                @unlink($fullPath);
            }
            $cnt++;
        }
        return $cnt;
    }

    /**
     * Use this to move the attached files to the directory in $dir
     *
     * If this is a single file then the $filepath is the full path (including filename)
     * of the destination location.
     *
     * If this is a multiple file field (isFieldArray() == true) then the $filepath
     * is the directory of the destination location.
     *
     * If you need more control get the uploadedFiles() and do it manually
     *
     * If the directory does not exist it will try to create it for you.
     *
     *
     * @see \Tk\UploadedFile::moveTo
     * @param string $destPath If not set then `$destPath = $this->dataPath . $this->destPath` is used
     * @return int The number of files moved
     * @throws \Tk\Form\Exception
     */
    public function moveFile($destPath = '')
    {
        $uploadedFiles = $this->getUploadedFiles();

        if (!$destPath) {
            throw new \Tk\Form\Exception('Please set a writable destination path.');
        }
        if (!is_dir($destPath)) {
            if (!@mkdir($destPath, \Tk\Config::getInstance()->getDirMask(), true)) {
                throw new \Tk\Form\Exception('File Permission Error: Cannot write to destination path.');
            }
        }

        $cnt = 0;
        try {
            foreach ($uploadedFiles as $uploadedFile) {
                $uploadedFile->move($destPath, $uploadedFile->getClientOriginalName());
//                $fullPath = $destPath . '/' . $uploadedFile->getClientOriginalName();
//                $uploadedFile->move($fullPath);
                $cnt++;
            }
        } catch (\Exception $e) {
            $this->addError($e->getMessage());
        }

        return $cnt;
    }

    /**
     * A basic file validation method.
     *
     * @return bool
     * @todo add ability to check file types from extension?
     */
    public function isValid()
    {
        if (!count($this->errors)) {
            if (!$this->hasFile()) {
                return true;
            }

            if (!count($this->getUploadedFiles()) && $this->isRequired()) {
                $this->addError(strip_tags('Please select a file to upload'));
            }
            /* @var UploadedFile $uploadedFile */
            foreach ($this->getUploadedFiles() as $uploadedFile) {
                if ($uploadedFile->getError() != \UPLOAD_ERR_OK) {
                    $this->addError(strip_tags($uploadedFile->getClientOriginalName()) . ': ' . $uploadedFile->getErrorMessage());
                }
                if ($uploadedFile->getSize() > $this->getMaxFileSize()) {
                    $this->addError(strip_tags($uploadedFile->getClientOriginalName()) . ': File to large');
                }
            }
        }
        // Return false if we have errors
        return !count($this->errors);
    }

    /**
     * Set the field value.
     * Set the exact value the field requires to function.
     *
     * @param mixed $value
     * @return $this
     */
    public function setValue($value)
    {
        if ($this->isArrayField()) {
            $this->value = json_encode($value);
        } else {
            if (is_array($value)) $value = current($value);
            $this->value = $value;
        }
        return $this;
    }

    /**
     * Get the field value(s).
     *
     * @return string|array
     */
    public function getValue()
    {
        if ($this->isArrayField()) {
            return json_decode($this->value);
        }
        return $this->value;
    }

    /**
     * if this field has a "{fieldName}-del" value then the file is marked for delete
     * Use this to know when to delete a file
     *
     * @return boolean
     */
    public function hasDelete()
    {
        return $this->delFile;
    }

    /**
     * @return string
     */
    public function getDeleteEventName()
    {
        return str_replace('.', '_', $this->makeId()) . '-del';
    }

    /**
     * Get the max filesize in bytes for this file field
     *
     * @return int
     */
    public function getMaxFileSize()
    {
        return $this->maxBytes;
    }

    /**
     * @param $bytes
     * @return $this
     */
    public function setMaxFileSize($bytes)
    {
        $this->maxBytes = (int)$bytes;
        return $this;
    }

    /**
     * Get a single uploaded file, default is to return the first file in the list.
     *
     * @param int $i For multiple files
     * @return null|UploadedFile
     */
    public function getUploadedFile($i = 0)
    {
        if (isset($this->uploadedFiles[$i]))
            return $this->uploadedFiles[$i];
    }

    /**
     * get a single uploaded file, if it is an array the first file with data
     * in the list will be returned.
     *
     * @return UploadedFile[]
     */
    public function getUploadedFiles()
    {
        return $this->uploadedFiles;
    }

    /**
     * Has there been a file submitted?
     *
     * return boolean
     */
    public function hasFile()
    {
        return (count($this->getUploadedFiles()) > 0);
    }
    
    

    /**
     * Get the element HTML
     *
     * @return string|\Dom\Template
     */
    public function show()
    {
        $this->setNotes('Max File Size: <b>' . \Tk\File::bytes2String($this->getMaxFileSize(), 0) . '</b><br/>' . $this->getNotes());

        $template = parent::show();

        $template->setAttr('element', 'data-maxsize', $this->getMaxFileSize());
        $request = \Tk\Request::createFromGlobals();
        if ($this->getValue() || $request->has($this->getDeleteEventName())) {
            $did = $this->makeId() . '-del';
            $template->setAttr('delete', 'id', $did);
            $template->setAttr('label', 'for', $did);
            $template->setAttr('delete', 'name', $did);
            $template->setAttr('delete', 'value', $did);
            $template->addCss('delWrapper', $did.'-wrap');
            if (\Tk\Request::createFromGlobals()->has($this->getDeleteEventName())) {
                $template->setAttr('delete', 'checked', 'checked');
            }
            $template->setVisible('delete');
        }

        return $template;
    }

    /**
     * makeTemplate
     *
     * @return \Dom\Template
     */
    public function __makeTemplate()
    {
        $xhtml = <<<HTML
<div class="tk-file-control">
  <input type="text" class="form-control form-control-lg" var="element"/>
  <div class="tk-file-delete" choice="delete" var="delWrapper">
    <input type="checkbox" class="" var="delete" id="file-del"/> <label for="file-del" var="label"> Delete File</label>
  </div>
</div>
HTML;
        return \Dom\Loader::load($xhtml);
    }
}