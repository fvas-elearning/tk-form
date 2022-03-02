<?php
namespace Tk\Form\Field;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class GmapAddress extends \Tk\Form\Field\Input
{

    /**
     * Get the element HTML
     *
     * @return string|\Dom\Template
     */
    public function show()
    {
        $template = parent::show();
        \Bs\Ui\Js::includeGoogleMaps($template, array('libraries' => 'places'));

        $template->appendJsUrl(\Tk\Uri::create('/vendor/ttek/tk-form/js/jquery.tkAddress.js'));
        $js = <<<JS
jQuery(function ($) {
    function init() {
      var form = $(this);
      form.find('input.tk-gmap-address').tkAddress();
    }
    $('form').on('init', document, init).each(init);
  
});
JS;
        $template->appendJs($js);

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
<div class="input-group input-append tk-gmap-address-group">
  <input type="text" var="element" class="form-control tk-gmap-address" />
  <span class="input-group-btn input-group-append">
    <button class="btn btn-default btn-toggle input-group-addon" type="button" title="Toggle Manual Address Input."><i class="fa fa-edit"></i></button>
  </span>
</div>

HTML;
        return \Dom\Loader::load($xhtml);
    }
}