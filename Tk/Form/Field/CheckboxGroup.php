<?php
namespace Tk\Form\Field;

/**
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class CheckboxGroup extends Select
{

    /**
     * @param string $name
     * @param Option\ArrayIterator $optionIterator
     */
    public function __construct($name, $optionIterator = null)
    {
        parent::__construct($name, $optionIterator);
        $this->setArrayField(true);
    }
    
//    /**
//     * Compare a value and see if it is selected.
//     *
//     * @param string $val
//     * @return bool
//     */
//    public function isSelected($val = '')
//    {
//        $value = $this->getValue();
//        if (is_array($value) ) {
//            if (in_array($val, $value)) {
//                return true;
//            }
//        }
//        return false;
//    }

    /**
     * Compare a value and see if it is selected.
     *
     * @param string $val
     * @return bool
     */
    public function isSelected($val = '')
    {
        $value = $this->getValue();
        if (is_array($value) ) {
            if (in_array($val, $value)) {
                return true;
            }
        } else if ($value !== null && $value == $val) {
            return true;
        }
        return false;
    }

    /**
     * Get the element HTML
     *
     * @return string|\Dom\Template
     */
    public function show()
    {
        $template = $this->getTemplate();

        /* @var \Tk\Form\Field\Option $option */
        foreach($this->getOptions() as $i => $option) {
            $tOpt = $template->getRepeat('option');

            if ($this->getOnShowOption()->isCallable()) {
                $b = $this->getOnShowOption()->execute($tOpt, $option, 'element');
                if ($b === false) return $template;
            }

            if ($option->hasAttr('disabled')) {
                $tOpt->setAttr('option', 'disabled', 'disabled');
                $tOpt->setAttr('element', 'disabled', 'disabled');
            }
            $tOpt->insertText('text', $option->getName());

            $tOpt->setAttr('element', 'value', $option->getValue());
            $tOpt->setAttr('element', 'name', $this->getFieldName());
            
            if ($this->isSelected($option->getValue())) {
                $tOpt->setAttr('element', 'checked', 'checked');
            }

            // All other attributes
            $tOpt->setAttr('option', $option->getAttrList());
            $tOpt->addCss('option', $option->getCssList());
            
            $tOpt->setAttr('element', $this->getAttrList());
            $tOpt->addCss('element', $this->getCssList());

            $tOpt->setAttr('element', 'id', $this->getId() . '-' . $i);
            $tOpt->setAttr('label', 'for', $this->getId() . '-' . $i);

            $tOpt->appendRepeat();
        }

        $this->decorateElement($template, 'group');
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
<div class="checkbox-group" var="group">
  <div class="checkbox" repeat="option" var="option">
    <label var="label">
      <input type="checkbox" var="element" />
      <span var="text"></span>
    </label>
  </div>
</div>
HTML;

        return \Dom\Loader::load($xhtml);
    }
    
}