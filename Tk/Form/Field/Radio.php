<?php
namespace Tk\Form\Field;

/**
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Radio extends Select
{


    /**
     * @param string $name
     * @param null|Option\ArrayIterator|array|\Tk\Db\Map\ArrayObject $optionIterator
     */
    public function __construct($name, $optionIterator = null)
    {
        parent::__construct($name, $optionIterator);
//        if ($optionIterator) {
//            $this->appendOptionIterator($optionIterator);
//        }
    }

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

        $checkedSet = false;
        /* @var \Tk\Form\Field\Option $option */
        foreach($this->getOptions() as $i => $option) {
            $tOpt = $template->getRepeat('option');

            if ($this->getOnShowOption()->isCallable()) {
                $b = $this->getOnShowOption()->execute($tOpt, $option, 'element');
                if ($b === false) return $template;
            }

            if (!$tOpt->keyExists('var', 'element')) continue;

            if ($option->isDisabled()) {
                $tOpt->setAttr('option', 'disabled', 'disabled');
                $tOpt->setAttr('element', 'disabled', 'disabled');
            }
            $tOpt->insertText('text', $option->getName());

            $tOpt->setAttr('element', 'value', $option->getValue());
            $tOpt->setAttr('element', 'name', $this->getFieldName());
            
            // allow only one radio to be selected.
            if ($this->isSelected($option->getValue()) && !$checkedSet) {
                $tOpt->setAttr('element', 'checked', 'checked');
            }

            // Set attributes
            $tOpt->setAttr('element', $this->getAttrList());
            $tOpt->addCss('element', $this->getCssString());

            $tOpt->setAttr('element', 'id', $this->getId() . '-' . $i);
            $tOpt->setAttr('label', 'for', $this->getId() . '-' . $i);
            $tOpt->setAttr('label', 'title', $option->getName());

            if ($this->getOnShowOption()->isCallable()) {
                $this->getOnShowOption()->execute($tOpt, $option, $checkedSet);
            }

            if ($this->getValue() == $option->getValue() && !$checkedSet) {
                $checkedSet = true;
            }
            $tOpt->appendRepeat();
        }

        $this->decorateElement($template);
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
<div>
  <div class="radio" repeat="option" var="option">
    <label var="label">
      <input type="radio" var="element" />
      <span var="text"></span>
    </label>
  </div>
</div>
HTML;

        return \Dom\Loader::load($xhtml);
    }
    
}