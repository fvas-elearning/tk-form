<?php
namespace Tk\Form\Event;

/**
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Button extends Iface
{

    /**
     * @var string
     */
    protected $iconLeft = '';

    /**
     * @var string
     */
    protected $iconRight = '';

    /**
     * @var string
     */
    private $type = 'button';


    /**
     * __construct
     *
     * @param string $name
     * @param callable $callback
     */
    public function __construct($name, $callback = null)
    {
        parent::__construct($name, $callback);

        // TODO: these need to be removed
        if ($name == 'save') {
            $this->setType('submit');
            $this->setIcon('fa fa-refresh');
        } else if ($name == 'update') {
            $this->setType('submit');
            $this->setIcon('fa fa-arrow-left');
        }
        $this->addCss('btn btn-default btn-once');
    }

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
     * @return string
     */
    public function getIcon()
    {
        return $this->iconLeft;
    }

    /**
     * @param string $icon
     * @return $this
     * @alias setIconLeft()
     */
    public function setIcon($icon)
    {
        return $this->setIconLeft($icon);
    }

    /**
     * @param string $icon
     * @return $this
     */
    public function setIconLeft($icon)
    {
        $this->iconLeft = $icon;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isIconRight()
    {
        return ($this->iconRight != '');
    }

    /**
     * @param string $icon
     * @return $this
     */
    public function setIconRight($icon)
    {
        $this->iconRight = $icon;
        return $this;
    }

    /**
     * @return string
     */
    public function getIconRight()
    {
        return $this->iconRight;
    }

    /**
     * Get the element HTML
     *
     * @return string|\Dom\Template
     */
    public function show()
    {
        $template = $this->getTemplate();
        if (!$template->isParsed() && $template->keyExists('var', 'element')) {
            if ($template->getVar('element')->nodeName == 'button') {
                $template->setAttr('element', 'type', $this->getType());
                $template->setAttr('element', 'name', $this->getEventName());
            }
            $template->setAttr('element', 'value', $this->getEventName());

            $template->setAttr('element', $this->getAttrList());
            $template->addCss('element', $this->getCssList());
            $template->insertText('text', $this->getLabel());

            if ($this->getIcon()) {
                $template->setVisible('iconL');
                $template->addCss('iconL', $this->getIcon());
            }

            if ($this->getIconRight()) {
                $template->setVisible('iconR');
                $template->addCss('iconR', $this->getIconRight());
            }
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
<button type="button" class="" var="element"><i var="iconL" choice="iconL"></i> <span var="text">Submit</span> <i var="iconR" choice="iconR"></i></button>
HTML;
        return \Dom\Loader::load($xhtml);
    }
}