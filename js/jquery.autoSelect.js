/*
 * Plugin: autoSelect
 * Version: 1.0
 * Date: 21/09/20
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2007 Michael Mifsud
 * @source http://stefangabos.ro/jquery/jquery-plugin-boilerplate-revisited/
 */


/**
 * <code>
 *   $(document).ready(function() {
 *     // attach the plugin to an element
 *     $('#element').autoSelect({'foo': 'bar'});
 *
 *     // call a public method
 *     $('#element').data('autoSelect').foo_public_method();
 *
 *     // get the value of a property
 *     $('#element').data('autoSelect').settings.foo;
 *   
 *   });
 * </code>
 */
;(function($) {
  var autoSelect = function(element, options) {
    // plugin vars
    var defaults = {
      theme: 'bootstrap4',
      sFormGroup: '.form-group',
      onFoo: function() {}
    };

    // Params
    var plugin = this;
    plugin.settings = {};
    var $element = $(element);    // Should be a select element
    var $form = null;
    var $formGroup = null;

    // constructor method
    plugin.init = function() {
      plugin.settings = $.extend({}, defaults, $(element).data(), options);
      if ($.fn.select2 === undefined) {
        console.error('Install jquery.select2.js plugin: https://select2.github.io/');
      }
      $form = $element.closest('form');
      $formGroup = $element.closest(plugin.settings.sFormGroup);
      $formGroup.addClass('form-group-select2');

      var ajax = null;
      if ($element.data('ajax')) {
        ajax = {
          url: $element.data('ajax'),
          dataType: 'json',
          delay: 250,
          data: function (params) {
            return $.extend($element.data(), {
              page: params.page,
              s: params.term // search term (supervisor name)
            });
          },
          processResults: function (data, params) {
            // parse the results into the format expected by Select2
            // since we are using custom formatting functions we do not need to
            // alter the remote JSON data, except to indicate that infinite
            // scrolling can be used
            params.page = params.page || 1;
            return {
              results: data.items,
              pagination: {
                //more: (params.page * 30) < data.total_count
                more: (params.page * 50) < data.total_count
              }
            };
          },
          cache: true
        };
      }


      $element.select2({
        placeholder: $element.attr('placeholder'),
        //theme: plugin.settings.theme,
        tags: true,
        //allowClear: true,
        tokenSeparators: [','],
        ajax: ajax,
        createTag: function(params) {   // Disable tag creation
          return undefined;
        }
      }).on('select2:unselecting', function (e) {
        // TODO: a bug when hitting backspace to delete a selection,
        //      stuffs the input up, something to figure out later...
        $(this).data('unselecting', true);
        return confirm('Are you sure you want to remove this record?');
      }).on('select2:open', function(e) {
        if ($(this).data('unselecting')) {
          $(this).select2('close').removeData('unselecting');
        }
      }).on('select2:selecting', function(e) {
        //console.log('select2:selecting');
      }).on('select2:select', function(e) {
        //console.log('select2:select');
      });

    };  // END init()


    // private methods
    //var foo_private_method = function() { };


    // public methods
    /**
     * 
     * @param text string
     * @param value string
     * @return Element The option element
     */
    plugin.insertOption = function(text, value) {
      // Insert a dynamic option
      if ($element.find('option[value='+value+']').length) return;
      var option = $('<option></option>')
        .attr('selected', true)
        .text(text)
        //.attr('disabled', 'disabled')
        .val(value);
      option.appendTo($element);
      $element.trigger('change');
      return option;
    };

    // call the "constructor" method
    plugin.init();
  };

  // add the plugin to the jQuery.fn object
  $.fn.autoSelect = function(options) {
    return this.each(function() {
      if (undefined === $(this).data('autoSelect')) {
        var plugin = new autoSelect(this, options);
        $(this).data('autoSelect', plugin);
      }
    });
  }

})(jQuery);



