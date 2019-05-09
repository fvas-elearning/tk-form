<?php
namespace Tk;

use Tk\Form\Field;
use Tk\Form\Event;
use Tk\Form\FormEvents;
use Tk\Ui\Element;


/**
 * The dynamic form processor
 *
 * `enctype` Attribute Values:
 * <code>
 *              Value                    |                 Description
 * --------------------------------------|---------------------------------------
 *  application/x-www-form-urlencoded    |  All characters are encoded before sent (this is default)
 *  multipart/form-data                  |  No characters are encoded. This value is required when you are using forms that have a file upload control
 *  text/plain                           |  Spaces are converted to "+" symbols, but no special characters are encoded
 * </code>
 *
 *
 * accept-charset is set as the $encoding parameter or use setEncoding()
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Form extends Form\Element
{

    const ENCTYPE_URLENCODED        = 'application/x-www-form-urlencoded';
    const ENCTYPE_MULTIPART         = 'multipart/form-data';
    const ENCTYPE_PLAIN             = 'text/plain';

    const METHOD_POST               = 'post';
    const METHOD_GET                = 'get';



    /**
     * @var string
     */
    protected $id = '';

    /**
     * @var Field\Iface[]
     */
    protected $fieldList = array();

    /**
     * @var Event\Iface
     */
    protected $triggeredEvent = null;

    /**
     * @var array
     */
    protected $loadArray = null;

    /**
     * if true the required HTML5 attribute will be rendered
     * @var bool
     */
    private $enableRequiredAttr = false;

    /**
     * @var null|\Tk\Event\Dispatcher
     */
    protected $dispatcher = null;

    /**
     * @var null|Form\Renderer\Iface
     */
    protected $renderer = null;

    /**
     * set to true after initForm() is called
     * @var bool
     */
    private $initialised = false;



    /**
     * @param string $formId
     */
    public function __construct($formId = 'form')
    {
        $this->id = $formId;
        $this->name = $formId;
        $this->setForm($this);
        $this->setAttr('method', self::METHOD_POST);
        $this->setAttr('action', \Tk\Uri::create());
        $this->setAttr('accept-charset', 'UTF-8');

        // TODO: Test how this affects EMS III
        // This is disabled by default because of error message rendering issues
        $this->setAttr('novalidate', 'novalidate');
    }

    /**
     * @param $formId
     * @return static
     */
    public static function create($formId = 'form')
    {
        $obj = new static($formId);
        return $obj;
    }

    /**
     * Get the unique id for this element
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return null|\Tk\Event\Dispatcher
     */
    public function getDispatcher()
    {
        return $this->dispatcher;
    }

    /**
     * @param null|\Tk\Event\Dispatcher $dispatcher
     */
    public function setDispatcher($dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * @return null|Form\Renderer\Iface
     */
    public function getRenderer()
    {
        return $this->renderer;
    }

    /**
     * @param null|Form\Renderer\Iface $renderer
     * @return $this
     */
    public function setRenderer($renderer)
    {
        $renderer->setForm($this);
        $this->renderer = $renderer;
        return $this;
    }


    /**
     * @return bool
     */
    public function isInitialised()
    {
        return $this->initialised;
    }

    /**
     * Useful for extended form objects
     * To be called after all fields are added and
     */
    public function initForm()
    {
        if ($this->initialised) return;
        $this->initialised = true;
        if ($this->getDispatcher()) {
            $e = new \Tk\Event\FormEvent($this);
            $e->set('form', $this);
            $this->getDispatcher()->dispatch($e, FormEvents::FORM_INIT);
        }
    }

    /**
     * Execute the object
     *
     * If an button is found and its event is executed the result is returned
     *
     * @param $request
     * @todo Can we use an array instead of the request here???
     */
    public function execute($request = null)
    {
        if (!$request) {
            $request = \Tk\Request::createFromGlobals()->request->all();
        }

        $this->initForm();      // TODO: not sure if this is a better place for it or not???

        // Load default field values
        $this->load($this->loadArray);
        if ($this->getDispatcher()) {
            $e = new \Tk\Event\FormEvent($this);
            $e->set('form', $this);
            $this->getDispatcher()->dispatch($e, FormEvents::FORM_LOAD);
        }
        $this->loadFields();

        // get the triggered event, this also setup the form ready to fire an event if present.
        /* @var Event\Iface|null $event */
        $event = $this->getTriggeredEvent($request);
        if (!$this->isSubmitted()) return;

        // Load request field values
        $cleanRequest = $this->cleanLoadArray($request);
        $this->load($cleanRequest);
        if ($this->getDispatcher()) {
            $e = new \Tk\Event\FormEvent($this);
            $e->set('form', $this);
            $this->getDispatcher()->dispatch($e, FormEvents::FORM_LOAD_REQUEST);
        }
        $this->loadFields();

        if ($this->getDispatcher()) {
            $e = new \Tk\Event\FormEvent($this);
            $e->set('form', $this);
            $this->getDispatcher()->dispatch($e, FormEvents::FORM_SUBMIT);
        }

        if ($event) {
            $event->execute();
        }
    }

    /**
     * Loads the fields with values from an array.
     * EG:
     *   $array['field1'] = 'value1';
     *
     * @param array|\ArrayObject $array
     * @return $this
     */
    protected function loadFields($array = array())
    {
        $array = array_merge($this->loadArray, $array);

        /* @var $field Field\Iface */
        foreach ($this->getFieldList() as $field) {
            if ($field instanceof Event\Iface) continue;
            $field->load($array);
        }

        return $this;
    }

    /**
     * Loads the fields with values from an array.
     * EG:
     *   $array['field1'] = 'value1';
     *
     * @param array $array
     * @return $this
     */
    public function load($array = array())
    {
        if ($this->loadArray === null) $this->loadArray = array();
        if (is_array($array)) {
            $this->loadArray = array_merge($this->loadArray, $array);
        }
        return $this;
    }
    
    /**
     * Clean the load() array
     *  o create a new raw array for any ArrayAccess objects like the request object
     *  o add array keys that the request modifies (request replaces '.' with '_') with field names
     *    this will not modify keys that a field does not exist for.
     * 
     * @param array|\ArrayAccess $array
     * @return array
     */
    protected function cleanLoadArray($array)
    {
        // Get values from ArrayAccess objects (IE: Request object)
        if ($array instanceof \ArrayAccess) {
            $a = array();
            foreach($array as $k => $v) $a[$k] = $v;
            $array = $a;
        }

        // Fix keys for conversions of '_' to '.' for fields that have been modified
        /* @var $field Field\Iface */
        foreach ($this->getFieldList() as $field) {
            $cleanName = str_replace('.', '_', $field->getName());
            if (array_key_exists($cleanName, $array) && !array_key_exists($field->getName(), $array)) {
                $array[$field->getName()] = $array[$cleanName];
            }

            // TODO HACK: Trying to fix the issue when no field data is sent and then only the default field values exist
            // TODO HACK: This is a mess, we need to go back to the drawing board on how we handle a request
            // TODO HACK:   the main issue is the ability to call load() multiple times, then the request array
            // TODO HACK:   when a value is null is ignored and only the loaded values exist when they should be
            // TODO HACK:   set to null
            // TODO HACK: The code below tries to fix this, I need to test a number of forms to ensure it does
            // TODO HACK:   not have any unexpected consequences when saving field data
            // TODO HACK:
            if ($field->isReadonly() || $field->isDisabled()) continue;
            if (!array_key_exists($field->getName(), $array)) {
                $array[$field->getName()] = null;
            }
            // TODO HACK END:

        }

        return $array;
    }

    /**
     * Get the field event to execute
     *
     * This will only return a valid value <b>after</b> the
     *   execute() method has been called.
     *
     * @param array $array
     * @return Event\Iface
     */
    public function getTriggeredEvent($array = null)
    {
        if ($array && !$this->triggeredEvent) {
            /* @var $field Field\Iface */
            foreach($this->fieldList as $field) {
                if ($field instanceof Event\Iface) {
                    if (isset($array[$field->getEventName()])) {
                        $this->triggeredEvent = $field;
                        break;
                    }
                }
            }
        }
        return $this->triggeredEvent;
    }


    /**
     * Add a callback to an event element,
     * The element must be of the type \Tk\Form\Field\Event
     *
     * @param string $fieldName
     * @param callable $callback
     * @return Event\Iface
     */
    public function addEventCallback($fieldName, $callback)
    {
        $fieldName = str_replace('[]', '', $fieldName);
        $field = $this->getField($fieldName);
        if ($field && $field instanceof Event\Iface) {
            $field->appendCallback($callback);
        } else {
            //\Tk\Log::warning('Event Field not found: `' . $fieldName . '`');
        }
        return $field;
    }

    /**
     * Check if the form has been submitted
     *
     * @return bool
     */
    public function isSubmitted()
    {
        if ($this->getTriggeredEvent()) {
            return true;
        }
        return false;
    }


    /**
     * @param Field\Iface $field
     * @param null|Field\Iface|string $refField
     * @return Field\Iface
     * @since 2.0.68
     */
    public function appendField(Field\Iface $field, $refField = null)
    {
        $field->setForm($this);
        if (is_string($refField)) {
            $refField = $this->getField(str_replace('[]', '', $refField));
        }
        if (!$refField || !$refField instanceof Field\Iface) {
            $this->fieldList[$field->getName()] = $field;
        } else {
            $newArr = array();
            /** @var Field\Iface $f */
            foreach ($this->fieldList as $f) {
                $newArr[$f->getName()] = $f;
                if ($f === $refField) $newArr[$field->getName()] = $field;
            }
            $this->fieldList = $newArr;
        }
        return $field;
    }

    /**
     * @param Field\Iface $field
     * @param null|Field\Iface|string $refField
     * @return Field\Iface
     * @since 2.0.68
     */
    public function prependField(\Tk\Form\Field\Iface $field, $refField = null)
    {
        $field->setForm($this);
        if (is_string($refField)) {
            $refField = $this->getField(str_replace('[]', '', $refField));
        }
        if (!$refField || !$refField instanceof Field\Iface) {
            $this->fieldList = array($field->getName() => $field) + $this->fieldList;
        } else {
            $newArr = array();
            /** @var Field\Iface $f */
            foreach ($this->fieldList as $f) {
                if ($f === $refField) $newArr[$field->getName()] = $field;
                $newArr[$f->getName()] = $f;
            }
            $this->fieldList = $newArr;
        }
        return $field;
    }

    /**
     * Remove a field from the form
     *
     * @param string $fieldName
     * @return Field\Iface|null returns null if not found
     */
    public function removeField($fieldName)
    {
        $field = $this->getField($fieldName);
        $fieldName = str_replace('[]', '', $fieldName);
        if (isset($this->fieldList[$fieldName])) {
            unset($this->fieldList[$fieldName]);
        }
        return $field;
    }

    /**
     * Return a field object or null if not found
     *
     * @param string $fieldName
     * @return null|Field\Iface
     */
    public function getField($fieldName)
    {
        $f = null;
        $fieldName = str_replace('[]', '', $fieldName);
        if (array_key_exists($fieldName, $this->fieldList)) {
            $f = $this->fieldList[$fieldName];
        }
        return $f;
    }

    /**
     * Set the field array
     *
     * @param array $arr
     * @return $this
     */
    public function setFieldList($arr = array())
    {
        $this->fieldList = $arr;
        return $this;
    }

    /**
     * Get the field array
     *
     * @return array|Element[]
     */
    public function getFieldList()
    {
        return $this->fieldList;
    }

    /**
     * Returns a form field value. Returns NULL if no field exists
     *
     * @param string $fieldName The element type name.
     * @return string|array
     */
    public function getFieldValue($fieldName)
    {
        $fieldName = str_replace('[]', '', $fieldName);
        $field = $this->getField($fieldName);
        if ($field instanceof Field\Iface) {
            return $field->getValue();
        }
        return null;
    }

    /**
     * Sets the value of an element type.
     *
     * @param string $fieldName The field name.
     * @param mixed $value The field value.
     * @return Field\Iface
     * @throws Exception
     */
    public function setFieldValue($fieldName, $value)
    {
        $fieldName = str_replace('[]', '', $fieldName);
        $field = $this->getField($fieldName);
        if (!$field || !$field instanceof Field\Iface) {
            throw new Exception('Type not found: `' . $fieldName . '`');
        }
        $field->setValue($value);
        return $field;
    }

    /**
     * Does this form contain errors
     *
     * @return bool
     */
    public function hasErrors()
    {
        /* @var $field Field\Iface */
        foreach ($this->fieldList as $field) {
            if ($field->hasErrors()) {
                return true;
            }
        }
        if (count($this->getErrors())) {
            return true;
        }
        return false;
    }

    /**
     * Get all the errors associated with this forms request
     *
     * @return array
     */
    public function getAllErrors()
    {
        $e = $this->errors;
        /* @var $field Field\Iface */
        foreach($this->getFieldList() as $field) {
            if ($field->hasErrors()) {
                $e[$field->getName()] = $field->getErrors();
            }
        }
        return $e;
    }

    /**
     * Adds field error.
     *
     * If the field is not found in the form then the error message is set to
     * the form error message.
     *
     * If $msg is null the field's error list is cleared
     *
     * @param string $fieldName A field name.
     * @param string $msg The error message.
     */
    public function addFieldError($fieldName, $msg = '')
    {
        $fieldName = str_replace('[]', '', $fieldName);
        /* @var $field Field\Iface */
        $field = $this->getField($fieldName);
        if ($field) {
            $field->addError($msg);
        } else {
            $this->addError($msg);
        }
    }

    /**
     * Adds form field errors from a map of (field name, list of errors) message pairs.
     *
     * If the field is not found in the form then the error message is added to
     * the form error messages.
     *
     * @param array $errors
     */
    public function addFieldErrors($errors)
    {
        foreach ($errors as $fieldName => $errorList) {
            $fieldName = str_replace('[]', '', $fieldName);
            $field = $this->getField($fieldName);
            if (!$field) {
                $this->addError($errorList);
            } else {
                $field->addError($errorList);
            }
        }
    }

    /**
     * This will return an array of the field's values,
     *
     * @param null|array|string $regex A regular expression or array of field names to get
     * @return array
     */
    public function getValues($regex = null)
    {
        $array = array();
        /* @var $field Field\Iface */
        foreach ($this->getFieldList() as $field) {
            if ($field instanceof Event\Iface) continue;
            if ($regex) {
                if (is_string($regex) && !preg_match($regex, $field->getName())) {
                    continue;
                } else if (is_array($regex) && !in_array($field->getName(), $regex)) {
                    continue;
                }
            }
            $value = $field->getValue();

            if (!$field->isArrayField() && is_array($value)) {
                foreach ($value as $k => $v) {  // pull values out if the element is not an array
                    $array[$k] = $v;
                }
            } else {
                $array[$field->getName()] = $value;
            }

        }
        return $array;
    }

    /**
     * @return bool
     */
    public function isEnableRequiredAttr()
    {
        return $this->enableRequiredAttr;
    }

    /**
     * @param bool $enableRequiredAttr
     */
    public function setEnableRequiredAttr($enableRequiredAttr = true)
    {
        $this->enableRequiredAttr = $enableRequiredAttr;
    }
    
    /**
     * @return null|string|\Dom\Template
     */
    public function show()
    {
        if ($this->getRenderer())
            return $this->getRenderer()->show();
        return '';
    }
    



    /**
     * Append an field to this form
     *
     * @param Field\Iface $field
     * @return Field\Iface
     * @deprecated Use appendField($field)
     * @remove 2.4.0
     */
    public function addField($field)
    {
        return $this->appendField($field);
    }

    /**
     * Add an element after another element
     *
     * @param string|Field\Iface|null $refField
     * @param Field\Iface $field
     * @return Field\Iface
     * @deprecated Use appendField($field, $refField)
     * @remove 2.4.0
     */
    public function addFieldAfter($refField, $field)
    {
        return $this->appendField($field, $refField);
    }

    /**
     * Add a field element before another element
     *
     * @param string|Field\Iface|null $refField
     * @param Field\Iface $field
     * @return Field\Iface
     * @deprecated Use prependField($field, $refField)
     * @remove 2.4.0
     */
    public function addFieldBefore($refField, $field)
    {
        return $this->prependField($field, $refField);
    }
}
