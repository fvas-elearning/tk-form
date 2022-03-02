<?php
namespace Tk\Form;

use Tk\Callback;
use Tk\CollectionTrait;
use Tk\ConfigTrait;
use Tk\Dom\AttributesTrait;
use Tk\Dom\CssTrait;
use Tk\Form;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
abstract class Element extends \Dom\Renderer\Renderer implements \Tk\InstanceKey, \Dom\Renderer\DisplayInterface
{
    use AttributesTrait;
    use CssTrait;
    use CollectionTrait;
    use ConfigTrait;


    /**
     * @var string
     */
    protected $name = '';

    /**
     * @var Form
     */
    protected $form = null;

    /**
     * @var array
     */
    protected $errors = array();
    
    /**
     * @var array
     * @deprecated
     */
    protected $paramList = null;

    /**
     * @var string
     */
    protected $label = null;

    /**
     * @var boolean
     */
    protected $showLabel = true;

    /**
     * @var string
     */
    protected $notes = null;

    /**
     * @var Callback
     */
    protected $onShow = null;

    /**
     * @var Callback
     */
    protected $onShowFieldGroup = null;


    /**
     * Set the name for this element
     *
     * @param $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;
        if (!$this->getLabel()) {
            $this->setLabel(self::makeLabel($this->getName()));
        }
        $this->onShow = Callback::create();
        $this->onShowFieldGroup = Callback::create();
        return $this;
    }

    /**
     * Get the unique name for this element
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return Callback
     */
    public function getOnShow()
    {
        return $this->onShow;
    }

    /**
     * Callback: function ($template, $element) { }
     *
     * @param callable|null $onShow
     * @return static
     * @deprecated use addOnShow($callable, $priority)
     */
    public function setOnShow($onShow)
    {
        $this->addOnShow($onShow);
        return $this;
    }

    /**
     * Callback: function (\Dom\Template $template, $element) { }
     *
     * @param callable $callable
     * @param int $priority
     * @return $this
     */
    public function addOnShow(callable $callable, $priority = Callback::DEFAULT_PRIORITY)
    {
        $this->getOnShow()->append($callable, $priority);
        return $this;
    }

    /**
     * @return Callback
     */
    public function getOnShowFieldGroup()
    {
        return $this->onShowFieldGroup;
    }

    /**
     * Callback: function (\Dom\Template $fieldGroup, \Tk\Form\Renderer\FieldGroup $element) { }
     *
     * @param callable|null $onShowFieldGroup
     * @return static
     * @deprecated use getOnShowFieldGroup()->append($callable, $priority)
     */
    public function setOnShowFieldGroup(callable $onShowFieldGroup)
    {
        $this->getOnShowFieldGroup()->append($onShowFieldGroup);
        return $this;
    }
    
    /**
     * Create a label from a name string
     * The default label uses the name (EG: `fieldNameSelect` -> `Field Name Select`)
     *
     * @param string $name
     * @return string
     */
    static function makeLabel($name)
    {
        $label = $name;
//        $label = preg_replace_callback('/[_\.-]([a-zA-Z_])/', function ($match) {    // Handle underscores
//            return strtoupper($match[1]);
//        }, $label);
        $label = str_replace(array('_', '-'), ' ', $label);
        $label = ucwords(preg_replace('/[A-Z]/', ' $0', $label));
        $label = preg_replace('/(\[\])/', '', $label);
        if (substr($label, -2) == 'Id') {
            $label = substr($label, 0, -3);
        }
        return $label;
    }

    /**
     * Get the unique ID for this field
     * Generally this is: "$prepend + form.id + element.name"
     *
     * If no form is set then the returned value is: "$prepend + element.name"
     *
     * @param string $prepend
     * @return string
     */
    protected function makeId($prepend = '')
    {
        if ($this->getForm() && $this->getForm() !== $this) {
            $prepend .= $this->getForm()->getId() . '-';
        }
        return $prepend . $this->getName();
    }

    /**
     * return the is attribute value
     *
     * @return string
     */
    public function getId()
    {
        return $this->makeId();
    }
    /**
     * Get the label of this field
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Set the label of this field
     *
     * @param string|null $str
     * @return $this
     */
    public function setLabel($str)
    {
        $this->label = $str;
        return $this;
    }

    /**
     * @return bool
     */
    public function hasShowLabel()
    {
        return $this->showLabel;
    }

    /**
     * @param bool $showLabel
     * @return $this
     */
    public function setShowLabel($showLabel)
    {
        $this->showLabel = $showLabel;
        return $this;
    }

    /**
     * Set the notes html
     *
     * @param string $html
     * @return $this
     */
    public function setNotes($html)
    {
        $this->notes = $html;
        return $this;
    }

    /**
     * Get any notes on this element
     *
     * @return string
     */
    public function getNotes()
    {
        return $this->notes;
    }

    /**
     * Set the form for this element
     *
     * @param Form $form
     * @return $this
     */
    public function setForm(Form $form)
    {
        $this->form = $form;
        // Not sure if this is the correct spot for this, but it need to be called by all fields after the form is set.
        if (!$this->getAttr('id')) {
            $this->setAttr('id', $this->getId());
        }
        return $this;
    }

    /**
     * Get the parent form element
     *
     * @return Form
     */
    public function getForm()
    {
        return $this->form;
    }

    /**
     * Add an error message html to the element
     *
     * @param string|array $msg
     * @return $this
     */
    public function addError($msg)
    {
        if (!is_array($msg)) {
            $msg = array($msg);
        }
        $this->errors = array_merge($this->errors, $msg);
        return $this;
    }

    /**
     * Get the element's error list as an array
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Set the error array.
     * Overwrites the existing error array.
     *
     * @param array $errors
     * @return $this
     */
    public function setErrors($errors = array())
    {
        $this->errors = $errors;
        return $this;
    }

    /**
     * Check if this element contains errors
     *
     * @return bool
     */
    public function hasErrors()
    {
        return (count($this->getErrors()) > 0);
    }

    /**
     * Create request keys with prepended string
     *
     * returns: `{instanceId}_{$key}`
     * 
     * The form->id is used as the instance key and must exist otherwise the key is returned unmodified.
     *
     * @param $key
     * @return string
     */
    public function makeInstanceKey($key)
    {
        if ($this->getForm()) {
            return $this->getForm()->getId() . '-' . $key;
        }
        return $key;
    }



    // TODO remove paramsList and methods when we are sure it is no longer used for anything

    /**
     * Get a parameter from the array
     *
     * @param $name
     * @return bool
     * @deprecated use get() and getRenderer()
     * @remove 2.4.0
     */
    public function getParam($name)
    {
        if (!empty($this->paramList[$name])) {
            return $this->paramList[$name];
        }
        return false;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return $this
     * @deprecated use set() and setRenderer()
     * @remove 2.4.0
     */
    public function setParam($name, $value)
    {
        $this->paramList[$name] = $value;
        return $this;
    }

    /**
     * Get the param array
     *
     * @return array
     * @deprecated
     * @remove 2.4.0
     */
    public function getParamList()
    {
        return $this->paramList;
    }

    /**
     * @param array $params
     * @return $this
     * @deprecated
     * @remove 2.4.0
     */
    public function setParamList($params)
    {
        $this->paramList = $params;
        return $this;
    }
}