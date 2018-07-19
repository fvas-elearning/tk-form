<?php
namespace Tk\Form\Field;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class DateRange extends \Tk\Form\Field\Iface
{
    const TYPE_DATE = 'input-daterange';
    const TYPE_DATETIME = 'input-datetimerange';

    /**
     * The type of date range picker
     * @var string
     */
    protected $type = self::TYPE_DATE;


    /**
     * @param array|\ArrayObject $values
     * @return $this
     */
    public function load($values)
    {
        $v = array();
        if (isset($values[$this->getName() . 'Start'])) {
            $v[$this->getName() . 'Start'] =  $values[$this->getName() . 'Start'];
        }
        if (isset($values[$this->getName() . 'End'])) {
            $v[$this->getName() . 'End'] =  $values[$this->getName() . 'End'];
        }
        if (!count($v)) $v = null;
        $this->setValue($v);
        return $this;
    }

    
    /**
     * Get the element HTML
     *
     * @return string|\Dom\Template
     */
    public function show()
    {
        $t = $this->getTemplate();

        $t->addCss('group', $this->getType());
        
        $this->decorateElement($t, 'dateStart');
        $this->decorateElement($t, 'dateEnd');

        $t->setAttr('dateStart', 'name', $this->getName() . 'Start');
        $t->setAttr('dateEnd', 'name', $this->getName() . 'End');
        $t->setAttr('dateStart', 'id', $this->getId().'Start');
        $t->setAttr('dateEnd', 'id', $this->getId().'End');

        // Set the field value
        $value = $this->getValue();
        if (is_array($value)) {
            $t->setAttr('dateStart', 'value', $value[$this->getName() . 'Start']);
            $t->setAttr('dateEnd', 'value', $value[$this->getName() . 'End']);
        }

        return $t;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return static
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * makeTemplate
     *
     * @return \Dom\Template
     */
    public function __makeTemplate()
    {

        $xhtml = <<<HTML
<div class="input-group" var="group">
  <input type="text" class="form-control dateStart" var="dateStart" data-parsley-error-message="Please enter a valid Start Date" />
  <span class="input-group-addon">to</span>
  <input type="text" class="form-control dateEnd" var="dateEnd" data-parsley-error-message="Please enter a valid End Date" />
</div>
HTML;
        return \Dom\Loader::load($xhtml);
    }
    
    
    
}