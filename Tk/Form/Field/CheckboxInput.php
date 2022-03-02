<?php
namespace Tk\Form\Field;


/**
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class CheckboxInput extends Input
{

    protected $cbPostfix = 'Alert';

    protected $cbTitle = 'Enable User Alert';


    /**
     * @param string $name
     * @param string $cbPostfix
     */
    public function __construct($name, $cbPostfix = 'Alert')
    {
        $this->setName($name);
        $this->cbPostfix = $cbPostfix;
    }

    /**
     * @param $name
     * @param string $cbPostfix
     * @return static
     */
    public static function createCheckboxInput($name, $cbPostfix = 'Alert')
    {
        return new static($name, $cbPostfix);
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
        $vals = array();
        if (array_key_exists($this->getName(), $values)) {
            $vals[$this->getName()] = $values[$this->getName()];
        }
        $vals[$this->getCbName()] = false;

        if (isset($values[$this->getCbName()]) && $this->getCbName() == $values[$this->getCbName()]) {
            $vals[$this->getCbName()] = true;
        }
        if (!count($vals)) $vals = null;
        $this->setValue($vals);
        return $this;
    }


    /**
     * @return string
     */
    public function getCbName(): string
    {
        return $this->getName() . ucwords($this->getCbPostfix());
    }

    /**
     * @return string
     */
    public function getCbPostfix(): string
    {
        return $this->cbPostfix;
    }

    /**
     * @param string $cbPostfix
     * @return CheckboxInput
     */
    public function setCbPostfix(string $cbPostfix): CheckboxInput
    {
        if ($cbPostfix)
            $this->cbPostfix = $cbPostfix;
        return $this;
    }

    /**
     * @return string
     */
    public function getCbTitle(): string
    {
        return $this->cbTitle;
    }

    /**
     * @param string $cbTitle
     * @return CheckboxInput
     */
    public function setCbTitle(string $cbTitle): CheckboxInput
    {
        $this->cbTitle = $cbTitle;
        return $this;
    }
    
    /**
     * Get the element HTML
     *
     * @return string|\Dom\Template
     */
    public function show()
    {
        $template = parent::show();

        $template->setAttr('checkbox', 'name', $this->getCbName());
        $template->setAttr('checkbox', 'value', $this->getCbName());
        if ($this->getCbTitle()) {
            $template->setAttr('checkbox', 'title', $this->getCbTitle());
        }
        // Element Values
        $vals = $this->getValue();
        if (isset($vals[$this->getCbName()]) && $vals[$this->getCbName()])
            $template->setAttr('checkbox', 'checked', 'checked');
        if (isset($vals[$this->getName()]) && $vals[$this->getName()])
            $template->setAttr('element', 'value', $vals[$this->getName()]);


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
<div class="input-group">
    <div class="input-group-prepend">
      <div class="input-group-text" var="cbGroup"><input type="checkbox" var="checkbox" /></div>
    </div>
    <input type="text" class="form-control" var="element" />
</div>
HTML;
        return \Dom\Loader::load($xhtml);
    }


    /**
     * Set the input type value
     *
     * @param $type
     * @return $this
     */
    public function setType($type)
    {
        return $this;
    }
    
    
}