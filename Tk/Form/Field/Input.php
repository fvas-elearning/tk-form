<?php
namespace Tk\Form\Field;


/**
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Input extends Iface
{
    
    /**
     * @var string
     */
    private $type = 'text';


    /**
     * Set the input type value
     * 
     * @param $type
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return string
     */
    public function getType() 
    {
        return $this->type;
    }

    /**
     * Get the field value(s).
     *
     * @return string|array
     */
    public function getValue()
    {
        if (is_string(parent::getValue()))
            $this->setValue(trim(parent::getValue()));
        return parent::getValue();
    }
    
    /**
     * Get the element HTML
     *
     * @return string|\Dom\Template
     */
    public function show()
    {
        $template = $this->getTemplate();
        if (!$template->keyExists('var', 'element')) {
            return $template;
        }

        // Set the input type attribute
        $template->setAttr('element', 'type', $this->getType());

        // Set the field value
        if ($template->getVar('element')->nodeName == 'input' ) {
            $value = $this->getValue();
            if ($value !== null && !is_array($value)) {
                $template->setAttr('element', 'value', $value);
            }
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
<input type="text" var="element" class="form-control" />
HTML;
        return \Dom\Loader::load($xhtml);
    }
    
    
    
}