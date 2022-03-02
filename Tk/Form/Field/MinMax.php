<?php
namespace Tk\Form\Field;


/**
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class MinMax extends Iface
{

    protected $maxName = 'max';


    /**
     * __construct
     *
     * @param string $age
     * @param string $ageM
     * @throws \Tk\Form\Exception
     */
    public function __construct($age, $ageM)
    {
        parent::__construct($age);
        $this->maxName = $ageM;
        $this->setLabel(ucwords($age) . '/' . ucwords($ageM));
    }


    /**
     * @param array|\ArrayObject $values
     * @return $this
     */
    public function load($values)
    {
        $v = array();
        if (isset($values[$this->getName()])) {
            $v[$this->getName()] =  $values[$this->getName()];
        }
        if (isset($values[$this->maxName])) {
            $v[$this->maxName] =  $values[$this->maxName];
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

        $this->decorateElement($t, 'min');
        $this->decorateElement($t, 'max');

        $t->setAttr('min', 'name', $this->getName());
        $t->setAttr('max', 'name', $this->maxName);

        $t->setAttr('min', 'id', $this->getId().'_'.$this->getName());
        $t->setAttr('max', 'id', $this->getId().'_'.$this->maxName);

        // Set the input type attribute

        // Set the field value
        $value = $this->getValue();
        if (is_array($value)) {
            if (isset($value[$this->getName()]))
                $t->setAttr('min', 'value', $value[$this->getName()]);
            if (isset($value[$this->maxName]))
                $t->setAttr('max', 'value', $value[$this->maxName]);
        }

        return $t;
    }

    /**
     * makeTemplate
     *
     * @return \Dom\Template
     */
    public function __makeTemplate()
    {

        $xhtml = <<<HTML
<div class="input-group input-group-minmax">
    <span class="input-group-addon">Min</span>
    <input type="text" class="form-control" var="min" />
    <span class="input-group-addon">Max</span>
    <input type="text" class="form-control" var="max" />
</div>
HTML;
        return \Dom\Loader::load($xhtml);
    }
    
    
    
}