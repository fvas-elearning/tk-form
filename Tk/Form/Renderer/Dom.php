<?php
namespace Tk\Form\Renderer;

use Dom\Template;
use Tk\Form\Field;
use Tk\Form\Event;
use Tk\Form;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 * @deprecated Use the new Renderer\DomRenderer this should no longer be used.
 */
class Dom extends Iface
{

    /**
     * @var null|\Dom\Repeat
     */
    protected $formRow = null;


    /**
     * @param Form $form        //TODO: Once we remove the setFieldGroupRenderer() we can remove the form and use setForm()
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
     * @param string $fieldGroupClass
     * @return $this
     * @deprecated Use setFieldGroupRenderer
     */
    public function setFieldGroupClass($fieldGroupClass)
    {
        \Tk\Log::notice('Using deprecated function: \Tk\Form\Renderer\Dom::setFieldGroupClass()');
        return $this;
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
        $i = 0;
        $tabGroups = array();

        $fieldList = $this->groupFieldset($this->form->getFieldList());
        $fieldsetName = 'null';
        $this->formRow = null;
        /** @var \Dom\Repeat $setRow */
        $setRow = null;
        /* @var $field Field\Iface */
        foreach ($fieldList as $field) {

            if (!$field->getTabGroup()) {
                if (!$field->getFieldset()) {
                    $this->showField($field, $t, 'fields');
                } else {
                    if ($fieldsetName != $field->getFieldset()) {
                        if ($this->formRow) {
                            $this->formRow->appendRepeat();
                            $this->formRow = null;
                        }
                        if ($setRow) {
                            $setRow->appendRepeat('fields');
                        }
                        $setRow = $t->getRepeat('fieldset');
                        $setRow->insertText('legend', $field->getFieldset());
                        $setRow->addCss('fieldset', $field->getFieldsetCss());
                        $setRow->addCss('fieldset', preg_replace('/[^a-z0-9_-]/i', '', $field->getFieldset()) );

                    }
                    $this->showField($field, $setRow, 'fieldset');
                }

                $fieldsetName = $field->getFieldset();
                $i++;
            } else {
                if (!isset($tabGroups[$field->getTabGroup()])) {
                    $tabGroups[$field->getTabGroup()] = array();
                }
                $tabGroups[$field->getTabGroup()][] = $field;
            }
        }
        if ($this->formRow) {
            $this->formRow->appendRepeat();
            $this->formRow = null;
        }
        if ($setRow) {
            $setRow->appendRepeat('fields');
        }

        $fieldsetName = 'null';
        /** @var \Dom\Repeat $setRow */
        $setRow = null;
        $i = (count($tabGroups)%2) ? 0 : 1;
        foreach ($tabGroups as $gname => $group) {
            $tabBox = $t->getRepeat('tabBox');
            if (!$tabBox)
                throw new \Tk\Exception('No tabBox repeat available: `' . $gname . '`. Check you have not double parsed the template.');
            foreach ($group as $field) {
                $tabBox->setAttr('tabBox', 'id', $this->form->getId().$this->cleanName($gname));
                $tabBox->setAttr('tabBox', 'data-name', $gname);
                if (!$field->getFieldset()) {
                    $this->showField($field, $tabBox, 'tabBox');
                } else {
                    if ($fieldsetName != $field->getFieldset()) {
                        if ($this->formRow) {
                            $this->formRow->appendRepeat();
                            $this->formRow = null;
                        }
                        if ($setRow) {
                            $setRow->appendRepeat('tabBox');
                        }
                        $setRow = $tabBox->getRepeat('fieldset');
                        $setRow->insertText('legend', $field->getFieldset());
                        $setRow->addCss('fieldset', $field->getFieldsetCss());
                        $setRow->addCss('fieldset', preg_replace('/[^a-z0-9_-]/i', '', $field->getFieldset()));
                    }
                    $this->showField($field, $setRow, 'fieldset');
                }
                $fieldsetName = $field->getFieldset();
                $i++;
            }
            if ($this->formRow) {
                $this->formRow->appendRepeat();
                $this->formRow = null;
            }
            if ($setRow) {
                $setRow->appendRepeat('tabBox');
            }
            $tabBox->appendRepeat('tab-content');
        }

        if (count($tabGroups)) {
            $t->setVisible('tabs');
            $tabPainName = $this->form->getId().'-tabPane';
            $t->setAttr('tabs', 'id', $tabPainName);
        }
    }


    /**
     * @param string $str
     * @return string
     */
    protected function cleanName($str)
    {
        return preg_replace('/[^a-z0-9]/i', '_', $str);
    }

    /**
     * @param array $fieldList
     * @return array
     */
    public function groupFieldset($fieldList)
    {
        $sets = array();

        /* @var $field Field\Iface */
        foreach ($fieldList as $name => $field) {
            $setName = $field->getFieldset();
            if (!$setName) {
                $sets[][$name] = $field;
                continue;
            }
            if ($setName && !isset($sets[$setName])) $sets[$setName] = array();
            $sets[$setName][$name] = $field;
        }
        $grouped = array();
        foreach ($sets as $fieldList) {
            foreach ($fieldList as $name => $field) {
                $grouped[$name] = $field;
            }
        }
        return $grouped;
    }

    /**
     * @param Field\Iface $field
     * @param Template $t
     * @param string $var ???? Not being used???
     */
    protected function showField(Field\Iface $field, Template $t, $var = 'fields')
    {
        if ($field instanceof Event\Iface || $field instanceof Field\Hidden) {
            $html = $field->show();
            if ($html instanceof \Dom\Template) {
                $t->appendTemplate('events', $html);
            } else {
                $t->appendHtml('events', $html);
            }
        } else {
            // Or use a layout adapter type object
            $html = $field->show();
            if ($this->getFieldGroupRenderer()) {
                $this->getFieldGroupRenderer()->setLayoutCol(null);
                if ($this->getLayout()) {
                    $this->getFieldGroupRenderer()->setLayoutCol($this->getLayout()->getCol($field->getName()));
                }
                $this->getFieldGroupRenderer()->setField($field);
                $html = $this->getFieldGroupRenderer()->show();
            }

            if (!$this->getLayout()) {
                $formRow = $t->getRepeat('form-row');
                $formRow->addCss('form-row', 'tk-'.\Tk\ObjectUtil::basename($field) . '-row');
                $formRow->addCss('form-row', 'tk-'.$field->getId() . '-row');
                if ($html instanceof \Dom\Template) {
                    $formRow->appendTemplate('form-row', $html);
                } else {
                    $formRow->appendHtml('form-row', $html);
                }
                $formRow->appendRepeat();
            } else {
                $layoutCol = $this->getLayout()->getCol($field->getName());
                if ($this->formRow && $layoutCol->isRowEnabled()) {
                    $this->formRow->appendRepeat();
                    $this->formRow = null;
                }
                if (!$this->formRow || $layoutCol->isRowEnabled()) {
                    $this->formRow = $t->getRepeat('form-row');
                    $this->formRow ->addCss('form-row', 'tk-'.lcfirst(\Tk\ObjectUtil::basename($field)) . '-row');
                    $this->formRow ->addCss('form-row', 'tk-'.$field->getId() . '-row');
                }

                if ($html instanceof \Dom\Template) {
                    $this->formRow->appendTemplate('form-row', $html);
                } else {
                    $this->formRow->appendHtml('form-row', $html);
                }
            }

        }
    }

    /**
     * @return \Dom\Template
     */
    public function __makeTemplate()
    {
        $xhtml = <<<HTML
<div class="">
<!-- This binds too late if we want to access the tab events. moved to tk-base core.js -->
<!--<script src="/vendor/ttek/tk-form/js/form.js"></script>-->
  <form class="tk-form" var="form" role="form">
    <div class="alert alert-danger clear" choice="errors">
      <button data-dismiss="alert" class="close noblock">×</button>
      <h4><i class="icon-ok-sign"></i> <strong>Form Error</strong></h4>
      <span var="errors"></span>
    </div>

    <div class="tk-form-fields clearfix" var="fields">

      <div class="formTabs" var="tabs" choice="tabs">
        <div class="tab-content" var="tab-content">

          <div class="tab-pane" var="tabBox" repeat="tabBox">
            <fieldset var="fieldset" repeat="fieldset">
              <legend var="legend"></legend>
              <div class="form-row" var="form-row" repeat="form-row"></div>
            </fieldset>
            <div class="form-row" var="form-row" repeat="form-row"></div>
          </div>

        </div>
      </div>

      <fieldset var="fieldset" repeat="fieldset">
        <legend var="legend"></legend>
          <div class="form-row" var="form-row" repeat="form-row"></div>
      </fieldset>
      <div class="form-row" var="form-row" repeat="form-row"></div>
      
    </div>

    <div class="form-row tk-form-events clearfix" var="events"></div>
  </form>

</div>
HTML;

        return \Dom\Loader::load($xhtml);
    }
}
