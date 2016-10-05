<?php
namespace Tk\Form\Event;

/**
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Link extends Button
{
    /**
     * @var string|\Tk\Uri
     */
    protected $url = null;


    /**
     * __construct
     *
     * @param string $name
     * @param string|\Tk\Uri $url
     * @param string $icon
     */
    public function __construct($name, $url, $icon = '')
    {
        parent::__construct($name);
        
        if (!$url) {
            $url = \Tk\Uri::create();
        }
        $this->url = $url;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Get the element HTML
     *
     * @return string|\Dom\Template
     */
    public function getHtml()
    {
        $t = $this->getTemplate();
        
        if ($t->isParsed()) return '';

        if (!$t->keyExists('var', 'element')) {
            return '';
        }

        // Field name attribute
        //$t->setAttr('element', 'type', $this->getType());
        $t->setAttr('element', 'name', $this->getName());

        // All other attributes
        foreach($this->getAttrList() as $key => $val) {
            if ($val == '' || $val == null) {
                $val = $key;
            }
            $t->setAttr('element', $key, $val);
        }

        // Element css class names
        foreach($this->getCssClassList() as $v) {
            $t->addClass('element', $v);
        }

        $t->insertText('text', $this->getLabel());
        if ($this->getIcon()) {
            $t->setChoice('icon');
            $t->addClass('icon', $this->getIcon());
        }
        
        $t->setAttr('element', 'href', $this->getUrl());
        
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
<a class="btn" var="element"><i var="icon" choice="icon"></i> <span var="text">Link</span></a>
HTML;
        return \Dom\Loader::load($xhtml);
    }
}