<?php
namespace Tk\Form\Renderer;

use Dom\Template;
use Tk\Collection;
use Tk\Form\Field;
use Tk\Form\Event;
use Tk\Form;
use Tk\Str;
use Tk\Ui\Css;

/**
 * The new (2021) Form Renderer\DomRenderer should mirror the renderer of the Dom\Renderer class
 * however it is far more cleaner and maintainable.
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class DomRenderer extends Iface
{

    /**
     * @var null|\Dom\Repeat
     */
    protected $formRow = null;

    /**
     * @var array|\Dom\Template[]
     */
    protected $tabGroupTemplates = [];

    /**
     * @var array|\Dom\Template[]
     */
    protected $fieldsetTemplates = [];



    /**
     * @param Form $form    //TODO: Once we remove the setFieldGroupRenderer() we can remove the form and use setForm()
     * @return Dom
     */
    static function create($form)
    {
        $obj = new static($form);
        // TODO: remove this once we have fixed external dependant codes
        $obj->setFieldGroupRenderer(FieldGroup::create($form));
        return $obj;
    }

    /**
     * Render the field and return the template or html string
     *
     * @return \Dom\Template
     * @throws \Exception
     */
    public function show()
    {
        if ($this->getForm()->getDispatcher()) {
            $e = new \Tk\Event\FormEvent($this->getForm());
            $e->set('form', $this->getForm());
            $this->getForm()->getDispatcher()->dispatch(\Tk\Form\FormEvents::FORM_SHOW, $e);
        }

        $template = $this->getTemplate();
        if (!$template->keyExists('var', 'form')) {
            return $template;
        }

        // Field name attribute
        $template->setAttr('form', 'id', $this->getForm()->getId());

        // All other attributes
        $template->setAttr('form' ,$this->getForm()->getAttrList());

        // Element css class names
        $template->addCss('form', $this->getForm()->getCssList());

        // render form errors
        if ($this->getForm()->hasErrors()) {
            /* @var $field Field\Iface */
            foreach ($this->getForm()->getFieldList() as $field) {
                if ($field->hasErrors()) {
                    $field->addCss('errors');
                }
            }
            $estr = '';
            foreach ($this->getForm()->getErrors() as $error) {
                if ($error) {
                    $estr .= '<div class="field-error">' . htmlentities($error) . "</div>\n";
                }
            }
            if ($estr) {
                $template->appendHtml('errors', $estr);
                $template->setVisible('errors');
            }
        }

        $this->showFields($template);

        return $template;
    }

    /**
     * Render Fields
     *
     * @param Template $t
     * @throws \Exception
     */
    public function showFields(Template $t)
    {
        // Build a render tree so we render the tabGroups, fieldsets and fieldRows in the correct order
        $fields = $this->makeFieldRenderTree($this->form->getFieldList());
        $show = array();

        /* @var $children Field\Iface|array */
        foreach ($fields as $rowId => $children) {
            if ($rowId == 'events-00') continue;
            if (is_array($children) && substr($rowId,0 , 3) == 'tg-') {     // Render Tab group
                $tpl = $this->renderTabGroup(substr($rowId, 3), $children);
                $t->appendTemplate('tab-content', $tpl);
                $show['tabs'] = true;
            } else if (is_array($children) && substr($rowId,0 , 3) == 'fs-') {  // Render Fieldset
                $tpl = $this->renderFieldset(substr($rowId, 3), '', $children);
                $t->appendTemplate('fields', $tpl);
                $show['fields'] = true;
            } else {    // render single field
                $tpl = $this->renderField($children, $rowId);
                $t->appendTemplate('fields', $tpl);
                $show['fields'] = true;
            }
        }

        if (!empty($fields['events-00'])) {     // Render Events and Hidden fields last
            foreach ($fields['events-00'] as $colId => $field) {
                $tpl = $field->show();
                $t->appendTemplate('events', $tpl);
                $show['events'] = true;
            }
        }
        foreach ($show as $choice => $v) {
            $t->setVisible($choice, true);
        }
    }

    /**
     * @param string $tabGroup
     * @param Field\Iface[]|array $fields
     * @param Template $t
     */
    protected function renderTabGroup($tabGroup, $fields)
    {
        $t = $this->getTabGroupTemplate($tabGroup);
        $t->setAttr('tab-group', 'id', $this->getForm()->getId() . $this->cleanName($tabGroup));
        $t->setAttr('tab-group', 'data-name', $tabGroup);

        /* @var $children Field\Iface|array */
        foreach ($fields as $rowId => $children) {
            if (is_array($children) && substr($rowId,0 , 3) == 'fs-')   // Render Fieldset
                $tpl = $this->renderFieldset(substr($rowId, 3), $tabGroup, $children);
            else     // render single field
                $tpl = $this->renderField($children, $rowId);
            $t->appendTemplate('tab-group', $tpl);
        }
        return $t;
    }

    /**
     * @param string $fieldset
     * @param string $tabGroup
     * @param Field\Iface[]|array $fields
     * @param Template $t
     */
    protected function renderFieldset($fieldset, $tabGroup = '', $fields)
    {
        $t = $this->getFieldSetTemplate($fieldset, $tabGroup);
        $t->insertText('legend', $fieldset);
        $t->addCss('fieldset', lcfirst(preg_replace('/[^a-z0-9_-]/i', '', $fieldset)));
        //$t->addCss('fieldset', preg_replace('/[^a-z0-9_-]/i', '', $fieldset));

        $css = '';
        /** @var Field\Iface[] $children */
        foreach ($fields as $rowId => $children) {
            if (current($children)->getFieldsetCss())
                $css = current($children)->getFieldsetCss();
            $t->appendTemplate('fieldset', $this->renderField($children,$rowId));
        }
        $t->addCss('fieldset', $css);
        return $t;
    }

    /**
     * @param Field\Iface[]|array $fields
     * @param string $rowId
     * @param Template $t
     */
    protected function renderField($fields, $rowId = '')
    {
        $t = $this->getFieldTemplate();
        if ($rowId)
            $t->addCss('form-row', 'tk-'.$rowId);
        else
            $t->addCss('form-row', 'tk-row-'.current($fields)->getId());

        $rowCss = new Css();
        foreach ($fields as $field) {
            if ($field instanceof Event\Iface || $field instanceof Field\Hidden) continue;
            $html = $field->show();
            if ($this->getFieldGroupRenderer()) {
                $this->getFieldGroupRenderer()->setLayoutCol(null);
                if ($this->getLayout())
                    $this->getFieldGroupRenderer()->setLayoutCol($this->getLayout()->getCol($field->getName()));
                $this->getFieldGroupRenderer()->setField($field);
                $html = $this->getFieldGroupRenderer()->show();
                $rowCss->addCss($field->getFormGroupCss());
            }
            if ($html instanceof \Dom\Template)
                $t->appendTemplate('form-row', $html);
            else
                $t->appendHtml('form-row', $html);

            $t->addCss('form-row', $rowCss->getCssString());
        }
        return $t;
    }

    /**
     * @param array $fieldList
     * @return array
     */
    public function makeFieldRenderTree($fieldList)
    {
        $sets = array();
        $rowId = 0;
        /* @var $field Field\Iface */
        foreach ($fieldList as $name => $field) {
            if ($field instanceof Event\Iface || $field instanceof Field\Hidden) {
                $sets['events-00'][] = $field;
                continue;
            }
            $layoutCol = null;
            if ($this->getLayout())
                $layoutCol = $this->getLayout()->getCol($field->getName());
            if (!$layoutCol || $layoutCol->isRowEnabled()) {
                $rowId++;
            }
            if ($field->getTabGroup()) {
                if ($field->getFieldset()) {    // has tabGroup and fieldset
                    $sets['tg-' . $field->getTabGroup()]['fs-' . $field->getFieldset()]['row-'.$rowId][] = $field;
                } else {    // has tabgroup only
                    $sets['tg-' . $field->getTabGroup()]['row-'.$rowId][] = $field;
                }
            } else {
                if ($field->getFieldset()) {    // has fieldset
                    $sets['fs-' . $field->getFieldset()]['row-'.$rowId][] = $field;
                } else {    // standalone field row
                    $sets['row-'.$rowId][] = $field;
                }
            }
        }
        return $sets;
    }

    /**
     * @return \Dom\Template
     */
    public function getTabGroupTemplate($tabGroup)
    {
        if (!isset($this->tabGroupTemplates[$tabGroup])) {
            $xhtml = <<<HTML
<div class="tab-pane" var="tab-group"></div>
HTML;
            $this->tabGroupTemplates[$tabGroup] = \Dom\Loader::load($xhtml);
        }
        return $this->tabGroupTemplates[$tabGroup];
    }

    /**
     * @return \Dom\Template
     */
    public function getFieldsetTemplate($fieldset, $tabGroup = '')
    {
        if (!isset($this->fieldsetTemplates[$tabGroup][$fieldset])) {
            $xhtml = <<<HTML
    <fieldset var="fieldset">
      <legend var="legend"></legend>
    </fieldset>
HTML;
            $this->fieldsetTemplates[$tabGroup][$fieldset] =  \Dom\Loader::load($xhtml);
        }
        return $this->fieldsetTemplates[$tabGroup][$fieldset];
    }

    /**
     * @return \Dom\Template
     */
    public function getFieldTemplate()
    {
        $xhtml = <<<HTML
<div class="form-row" var="form-row"></div>
HTML;
        return \Dom\Loader::load($xhtml);
    }

    /**
     * @return \Dom\Template
     */
    public function __makeTemplate()
    {
        $xhtml = <<<HTML
<div>
  <form class="tk-form" var="form" role="form">
    <div class="alert alert-danger clear" choice="errors">
      <button data-dismiss="alert" class="close noblock">×</button>
      <h4><i class="icon-ok-sign"></i> <strong>Form Error</strong></h4>
      <span var="errors"></span>
    </div>

    <div class="formTabs" var="tabs" choice="tabs">
      <div class="tab-content" var="tab-content"></div>
    </div>

    <div class="tk-form-fields clearfix" var="fields" choice="fields"></div>

<!--    <div class="form-row tk-form-events clearfix" var="events" choice="events"></div>-->
    <div class="form-group tk-form-events clearfix" var="events" choice="events"></div>
  </form>

</div>
HTML;

        return \Dom\Loader::load($xhtml);
    }

    /**
     * @param string $str
     * @return string
     */
    protected function cleanName($str)
    {
        return preg_replace('/[^a-z0-9]/i', '_', $str);
    }
}
