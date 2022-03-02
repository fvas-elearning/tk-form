<?php
namespace Tk\Form\Field;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2017 Michael Mifsud
 * @see https://select2.github.io/examples.html
 */
class AutoSelect extends Select
{

    /**
     * @var \Uni\Db\Course
     */
    protected $course = null;

    /**
     * @var \Tk\Ui\Dialog\Dialog
     */
    protected $dialog = null;


    /**
     * @param string $name
     * @param Option\ArrayIterator|array|\Tk\Db\Map\ArrayObject|null $optionIterator
     * @param \Tk\Ui\Dialog\Dialog|null $dialog
     * @return static
     */
    public static function createAutoSelect(string $name, $optionIterator = null, $dialog = null)
    {
        $obj = new static($name, $optionIterator);
        if ($dialog)
            $obj->setDialog($dialog);
        return $obj;
    }

    /**
     * @return \Tk\Ui\Dialog\Dialog
     */
    public function getDialog(): \Tk\Ui\Dialog\Dialog
    {
        return $this->dialog;
    }

    /**
     * @param \Tk\Ui\Dialog\Dialog $dialog
     * @return AutoSelect
     */
    public function setDialog(\Tk\Ui\Dialog\Dialog $dialog): AutoSelect
    {
        $this->dialog = $dialog;
        return $this;
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
        if (is_array($value) && in_array($val, $value)) return true;
        else if ($value !== null && $value == $val) return true;
        return false;
    }

    /**
     * Get the element HTML
     *
     * @return string|\Dom\Template
     * @throws \Dom\Exception
     */
    public function show()
    {
        $template = parent::show();

        $template->appendJsUrl(\Tk\Uri::create('/vendor/ttek/tk-form/js/jquery.autoSelect.js'));

        if ($this->dialog) {
            $template->setVisible('enableCreate');
            $template->setAttr('createBtn', 'data-modal-id', $this->dialog->getId());
            $template->appendBodyTemplate($this->dialog->show());
        }

        $js = <<<JS
jQuery(function ($) {
  if ($.fn.autoSelect !== undefined) {
     $('select.tk-auto-select').autoSelect();
  }
  // Open the supervisor select dialog on button click
  $('.tk-form').on('click', '.btn-create-dialog', function (e) {
    $('#'+$(this).data('modalId')).modal('show');
  });
});
JS;
        $template->appendJs($js);

        return $template;
    }

    /**
     * @return \Dom\Template
     */
    public function __makeTemplate()
    {
        $xhtml = <<<HTML
<div class="input-group" var="input-group">
  <select var="element" class="form-control tk-auto-select"><option repeat="option" var="option"></option></select>
  <span class="input-group-append input-group-btn" choice="enableCreate">
    <button class="btn btn-default btn-create-dialog" type="button" var="createBtn"><i class="fa fa-plus"></i></button>
  </span>
</div>
HTML;
        return \Dom\Loader::load($xhtml);
    }

}