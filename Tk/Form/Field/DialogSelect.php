<?php
namespace Tk\Form\Field;


use Tk\Form\Field\Option\ArrayIterator;

/**
 * Allows for a dialog with a form to be added to a select field
 *    for creating new records and selecting in one field
 *
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class DialogSelect extends \Tk\Form\Field\Select
{

    /**
     * @var \Tk\Ui\Dialog\Dialog|null
     */
    protected $dialog = null;


    /**
     * @param string $name
     * @param null|ArrayIterator|array|\Tk\Db\Map\ArrayObject $optionIterator
     * @param \Tk\Form|null $form
     * @param string $title
     */
    public function __construct($name, $optionIterator = null, $form = null, $title = '')
    {
        parent::__construct($name, $optionIterator);
        if ($form) {
            if (!$title)    // TODO: this may need a better auto naming system
                $title = 'Create ' . ucwords($name);
            $this->dialog = \Tk\Ui\Dialog\JsonForm::createJsonForm($form, $title);
        }
    }

    /**
     * @param string $name
     * @param null|ArrayIterator|array|\Tk\Db\Map\ArrayObject $optionIterator
     * @param \Tk\Form|null $form
     * @param string $title
     * @return static
     */
    public static function createDialogSelect($name, $optionIterator = null, $form = null, $title = '')
    {
        return new static($name, $optionIterator, $form, $title);
    }

    /**
     * Get the element HTML
     *
     * @return \Dom\Template
     * @throws \Exception
     */
    public function show()
    {
        $this->dialog->addCss('tk-dialog-select');
        $this->dialog->setAttr('data-select-id', $this->getId());
        $this->dialog->setAttr('data-form-id', $this->getForm()->getId());
        $this->dialog->setAttr('data-reset-on-hide', true);

        $template = parent::show();

        // We appendToThe body as to avoid a nested form issue.
        $template->appendBodyTemplate($this->dialog->show());
        $template->setAttr('modelBtn', 'data-target', '#'.$this->dialog->getId());
        $template->setAttr('modelBtn', 'data-toggle', 'modal');
        $template->setAttr('modelBtn', 'title', $this->dialog->getTitle());

        $js = <<<JS
jQuery(function($) {  
  
  function init() {
    var form = $(this);
    var dialog = form.closest('.modal');
    dialog.on('DialogForm:submit', function (e, data) {
      // Add the new contact/id to the select and select it
      var select = $('#' + $(this).data('formId') + ' #' + $(this).data('selectId'));
      var option = $('<option></option>')
          .attr('selected', 'selected')
          .text(data.name + ' (' + data.email + ')')
          //.attr('disabled', 'disabled')
          .val(data.id);
      option.appendTo(select);
      select.trigger('change');
    }).on('DialogForm:error', function (e, xhr) {
      //var dialog = $(this).closest('.modal');
      if (dialog.attr('id') !== $(e.currentTarget).attr('id')) return;
      console.log('\Tk\Form\Field\DialogSelect: DialogForm:error');
    });
  };
  
  $('.tk-dialog-select form').on('init', 'body', init).each(init);
});
JS;

        $template->appendJs($js);

        return $template;
    }

    /**
     * @return \Tk\Ui\Dialog\Dialog|null
     */
    public function getDialog(): ?\Tk\Ui\Dialog\Dialog
    {
        return $this->dialog;
    }

    /**
     * @param \Tk\Ui\Dialog\Dialog|null $dialog
     * @return DialogSelect
     */
    public function setDialog(?\Tk\Ui\Dialog\Dialog $dialog): DialogSelect
    {
        $this->dialog = $dialog;
        return $this;
    }

    /**
     * makeTemplate
     *
     * @return \Dom\Template
     */
    public function __makeTemplate()
    {
        $xhtml = <<<HTML
<div class="input-group tk-create-select">
  <select var="element" class="form-control">
    <option repeat="option" var="option"></option>
  </select>
  <span class="input-group-btn">
    <button type="button" class="btn btn-primary" title="" var="modelBtn"><i class="fa fa-plus"></i></button>
  </span>
</div>
HTML;

        return \Dom\Loader::load($xhtml);
    }

}