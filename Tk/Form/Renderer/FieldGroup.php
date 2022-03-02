<?php
namespace Tk\Form\Renderer;

use Tk\Callback;
use Tk\Form\Field;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class FieldGroup extends \Dom\Renderer\Renderer implements \Dom\Renderer\DisplayInterface
{

    /**
     * @var \Tk\Form
     */
    protected $form = null;

    /**
     * @var Field\Iface
     */
    protected $field = null;

    /**
     * @var null|LayoutCol
     */
    protected $layoutCol = null;

    /**
     * @var Callback
     */
    protected $onShow = null;


    /**
     * @param \Tk\Form $form
     */
    public function __construct($form)
    {
        $this->form = $form;
        $this->onShow = Callback::create();
    }

    /**
     * @param \Tk\Form $form
     * @return FieldGroup|static
     */
    static function create($form)
    {
        $obj = new static($form);
        return $obj;
    }

    /**
     * @return \Tk\Form
     */
    public function getForm()
    {
        return $this->form;
    }

    /**
     * @param \Tk\Form $form
     * @return static
     */
    public function setForm(\Tk\Form $form)
    {
        $this->form = $form;
        return $this;
    }

    /**
     * @return Field\Iface
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * @param Field\Iface $field
     * @return $this
     */
    public function setField(Field\Iface $field)
    {
        $this->field = $field;
        return $this;
    }

    /**
     * @return null|LayoutCol
     */
    public function getLayoutCol()
    {
        return $this->layoutCol;
    }

    /**
     * @param null|LayoutCol $layoutCol
     * @return $this
     */
    public function setLayoutCol($layoutCol)
    {
        $this->layoutCol = $layoutCol;
        return $this;
    }

    /**
     * @return Callback
     */
    protected function getOnShow()
    {
        return $this->onShow;
    }

    /**
     * @param callable|null $onShow
     * @return $this
     * @deprecated use getOnShow()->append($callable, $priority)
     */
    public function setOnShow($onShow)
    {
        $this->getOnShow()->append($onShow);
        return $this;
    }

    /**
     * @return \Dom\Renderer\Renderer|\Dom\Template|null
     */
    public function show()
    {
        $template = clone $this->getTemplate();     // Get a fresh template for each field

        $this->showErrors($template);
        $this->showLabel($template);
        $this->showNotes($template);
        $this->showField($template);

        // Form Group Styles
        $template->addCss('form-group',  'tk-'.strtolower( \Tk\Dom\CssTrait::cleanCss($this->getField()->getName()) ));
        try {
            $reflect = new \ReflectionClass($this->getField());
            $template->addCss('form-group',  'tk-'.strtolower( $reflect->getShortName() ));
        } catch (\ReflectionException $e) { }

        if ($this->getLayoutCol()) {
            $template->setAttr('form-group', $this->getLayoutCol()->getLayout()->getAttrList());
            $template->addCss('form-group', $this->getLayoutCol()->getLayout()->getCssList());

            $template->setAttr('form-group', $this->getLayoutCol()->getAttrList());
            $template->addCss('form-group', $this->getLayoutCol()->getCssList());
        }

        $this->getOnShow()->execute($template, $this->getField());

        return $template;
    }

    /**
     * @param \Dom\Template $template
     */
    protected function showField($template)
    {
        $html = $this->getField()->getTemplate();
        if ($html instanceof \Dom\Template) {
            $template->replaceTemplate('element', $html);
        } else {
            $template->replaceHtml('element', $html);
        }
        $this->getField()->getOnShowFieldGroup()->execute($template, $this);
    }

    /**
     * @param \Dom\Template $template
     */
    protected function showErrors($template)
    {
        if ($this->getField()->hasErrors()) {
            $fieldTemplate = $this->getField()->getTemplate();
            if ($fieldTemplate instanceof \Dom\Template) {
                $fieldTemplate->addCss('element', 'is-invalid');
                $template->addCss('form-group', 'tk-is-invalid');
            }
            $estr = '';
            foreach ($this->getField()->getErrors() as $error) {
                if ($error)
                    $estr .= $error . "<br/>\n";
            }
            if ($estr) {
                $estr = substr($estr, 0, -6);
                $template->appendHtml('error', $estr);
                $template->setVisible('error');
            }
        }
    }

    /**
     * @param \Dom\Template $template
     */
    protected function showLabel($template)
    {
        if ($this->getField()->hasShowLabel() && $this->getField()->getLabel() !== null) {
            $label = $this->getField()->getLabel();
            if ($label) $label .= ':';
            if ($this->getField()->isRequired()) {
                $template->addCss('form-group', 'required');
                $template->setAttr('label', 'title', 'Required');
            }
            $template->appendHtml('label', htmlentities($label));
            $template->setAttr('label', 'for', $this->getField()->getAttr('id'));
            $template->setVisible('label');
        }
    }

    /**
     * @param \Dom\Template $template
     */
    protected function showNotes($template)
    {
        if ($this->getField()->getNotes() !== null) {
            $template->setVisible('notes');
            $template->appendHtml('notes', $this->getField()->getNotes());
        }
    }

    /**
     * makeTemplate
     *
     * @return \Dom\Template
     */
    protected function __makeTemplate()
    {
        $xhtml = <<<HTML
<div class="form-group" var="form-group">
  <label class="" var="label" choice="label"></label>
  <div var="element" class="controls"></div>
  <div class="invalid-feedback" var="error" choice="error"></div>
  <small class="form-text text-muted" var="notes" choice="notes"></small>
</div>
HTML;

        return \Dom\Loader::load($xhtml);
    }

}
