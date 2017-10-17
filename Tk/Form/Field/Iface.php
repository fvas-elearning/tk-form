<?php
namespace Tk\Form\Field;

use Tk\Form\Exception;
use Tk\Form;

/**
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
abstract class Iface extends \Tk\Form\Element implements \Dom\Renderer\RendererInterface
{

    /**
     * @var mixed|null
     */
    protected $value = null;

    /**
     * @var bool
     */
    protected $required = false;

    /**
     * @var string
     */
    protected $pattern = '';

    /**
     * @var bool
     */
    protected $disabled = false;

    /**
     * @var bool
     */
    protected $readonly = false;

    /**
     * This will be true if the element is an array IE: name="title[]"
     * the "[]" will be removed from the name
     * @var bool
     */
    protected $arrayField = false;
        
    /**
     * @var string
     */
    protected $fieldset = '';

    /**
     * @var string
     */
    protected $tabGroup = '';

    /**
     * @var mixed
     */
    protected $template = null;
    
    

    /**
     * __construct
     *
     * @param string $name
     */
    public function __construct($name)
    {
        $this->setName($name);
    }
     

    /**
     * Set the name for this element
     *
     * When using the element with an array name (EG: 'name[]')
     * The '[]' are removed from the name but the isArray value is set to true.
     *
     * NOTE: only single dimensional numbered arrays are supported,
     *  Multidimensional or named arrays are not.
     *  Invalid field name examples are:
     *   o 'name[key]'
     *   o 'name[][]'
     *   o 'name[key][]'
     *
     * @param $name
     * @return $this
     * @throws Exception
     */
    public function setName($name)
    {
        $n = $name;
        if (substr($n, -2) == '[]') {
            $this->arrayField = true;
            $n = substr($n, 0, -2);
        }
        if (strstr($n, '[') !== false) {
            throw new Exception('Invalid field name: ' . $n);
        }
        parent::setName($n);
        return $this;
    }

    /**
     * Get the unique name for this element
     *
     * @return string
     */
    public function getFieldName()
    {
        $n = $this->getName();
        if ($this->isArrayField()) {
            $n .= '[]';
        }
        return $n;
    }

    /**
     * Assumes the field value resides within an array
     * EG:
     *   array(
     *    'fieldName1' => 'value1',
     *    'fieldName2' => 'value2',
     *    'fieldName3[]' => array('value3.1', 'value3.2', 'value3.3', 'value3.4'),  // same as below
     *    'fieldName3' => array('value3.1', 'value3.2', 'value3.3', 'value3.4')     // same
     * );
     *
     * This objects load() method is called by the form's execute() method
     *
     * @param array|\ArrayObject $values
     * @return $this
     */
    public function load($values)
    {
        // When the value does not exist it is ignored (may not be the desired result for unselected checkbox or empty select box)
        if (array_key_exists($this->getName(), $values)) {
            $this->setValue($values[$this->getName()]);
        }
        return $this;
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
        $this->value = $value;
        return $this;
    }

    /**
     * Get the field value(s).
     *
     * @return string|array
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Does this fields data come as an array.
     * If the name ends in [] then it will be flagged as an arrayField.
     *
     * EG: name=`name[]`
     *
     * @return boolean
     */
    public function isArrayField()
    {
        return $this->arrayField;
    }

    /**
     * Set to true if this element is an array set
     *
     * EG: name=`name[]`
     *
     * @param $b
     * @return $this
     */
    public function setArrayField($b)
    {
        $this->arrayField = $b;
        return $this;
    }


    /**
     * Add a CSS Class name to the node
     *
     * @param string $class
     * @param bool $fixName
     * @return Form\Element|Iface
     */
    public function addCss($class, $fixName = true)
    {
        return parent::addCss($class, $fixName);
    }

    /**
     * Remove a CSS Class name from the node
     *
     * @param string $class
     * @param bool $fixName
     * @return Form\Element|Iface
     */
    public function removeCss($class, $fixName = true)
    {
        return parent::removeCss($class, $fixName);
    }


    /**
     * test if the array is sequential or associative
     *
     * @param $arr
     * @return bool
     */
    protected function isAssoc($arr)
    {
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

    /**
     * @return string
     */
    public function getFieldset()
    {
        return $this->fieldset;
    }

    /**
     * @param string $fieldset
     * @return $this
     */
    public function setFieldset($fieldset)
    {
        $this->fieldset = $fieldset;
        return $this;
    }

    /**
     * @return string
     */
    public function getTabGroup()
    {
        return $this->tabGroup;
    }

    /**
     * @param string $tabGroup
     * @return $this
     */
    public function setTabGroup($tabGroup)
    {
        $this->tabGroup = $tabGroup;
        return $this;
    }




    /**
     * @return boolean
     */
    public function isDisabled()
    {
        return $this->hasAttr('disabled');
    }

    /**
     * @param boolean $disabled
     * @return $this
     */
    public function setDisabled($disabled = true)
    {
        if ($disabled)
            $this->setAttr('disabled');
        else
            $this->removeAttr('disabled');
        return $this;
    }

    /**
     * @return boolean
     */
    public function isReadonly()
    {
        return $this->hasAttr('readonly');
    }

    /**
     * @param boolean $readonly
     * @return $this
     */
    public function setReadonly($readonly = true)
    {
        if ($readonly)
            $this->setAttr('readonly');
        else
            $this->removeAttr('readonly');
        return $this;
    }

    /**
     * isRequired
     *
     * @return boolean
     */
    public function isRequired()
    {
        return $this->hasAttr('required');
    }

    /**
     * setRequired
     *
     * @param boolean $required
     * @return $this
     */
    public function setRequired($required = true)
    {
        if ($required) {
            if (!$this->getForm() || $this->getForm()->isEnableRequiredAttr()) {
                $this->setAttr('required');
            }
        } else {
            $this->removeAttr('required');
        }
        return $this;
    }

    /**
     * @return string
     */
    public function getPattern()
    {
        return $this->getAttr('pattern');
    }

    /**
     * @param string $pattern
     * @return $this
     */
    public function setPattern($pattern)
    {
        $this->setAttr('pattern', $pattern);
        return $this;
    }

    /**
     * Decorate an element template
     *
     * @param \Dom\Template $t
     * @return \Dom\Template|string
     */
    public function decorateElement(\Dom\Template $t, $var = 'element')
    {
        if (!$t->keyExists('var', $var)) {
            return $t;
        }

        // Field name attribute
        $t->setAttr($var, 'name', $this->getFieldName());

        if ($this->isRequired() && !$this->getForm() && $this->getForm()->isEnableRequiredAttr()) {
            $this->setRequired(false);
            //$t->setAttr($var, 'required', 'required');
        }

//        if ($this->isReadonly()) {
//            $t->setAttr($var, 'readonly', 'readonly');
//        }

//        if ($this->isDisabled()) {
//            $t->setAttr($var, 'disabled', 'disabled');
//        }

        // Add attributes
        foreach($this->getAttrList() as $key => $val) {
            if ($val === '' || $val === null) $val = $key;
            $t->setAttr($var, $key, $val);
        }

        // Add element css class labels
        foreach($this->getCssList() as $v) {
            $t->addCss($var, $v);
        }

        return $t;
    }


    /* \Dom\Renderer\RendererInterface */

    /**
     * Set a new template for this renderer.
     *
     * @param \Dom\Template|string $template
     */
    public function setTemplate($template)
    {
        $this->template = $template;
    }

    /**
     * Get the template
     * This method will try to call the magic method __makeTemplate
     * to create a template if non exits.
     *
     * @return \Dom\Template|string
     */
    public function getTemplate()
    {

        $magic = '__makeTemplate';
        if (!$this->hasTemplate() && method_exists($this, $magic)) {
            $this->template = $this->$magic();
        }
        return $this->template;
    }

    /**
     * Test if this renderer has a template and is not NULL
     *
     * @return bool
     */
    public function hasTemplate()
    {
        if ($this->template) {
            return true;
        }
        return false;
    }
    
}