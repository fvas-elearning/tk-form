/*
 * Plugin: InputLock
 * Version: 1.0
 * Date: 11/05/17
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2007 Michael Mifsud
 * @source http://stefangabos.ro/jquery/jquery-plugin-boilerplate-revisited/
 */

/**
 * Use this script to create a lockable input text field
 *
 * The user must click the unlock button before they are able to edit its value
 *
 * Requires:
 * <code>
 *   $template->appendJsUrl(\Tk\Uri::create('/src/Tk/Form/Field/jquery.tkInputLock.js'));
 * </code>
 *
 * <code>
 *   $(document).ready(function() {
 *     // attach the plugin to an element
 *     $('#element').tkInputLock({'foo': 'bar'});
 *
 *     // call a public method
 *     $('#element').data('tkInputLock').foo_public_method();
 *
 *     // get the value of a property
 *     $('#element').data('tkInputLock').settings.foo;
 *   });
 * </code>
 *
 */

(function ($) {
  /**
   *
   * @param element Form
   * @param options Object
   */
  var tkInputLock = function (element, options) {
    // plugin vars
    var defaults = {
      lockIcon: 'fa-lock',
      unlockIcon: 'fa-unlock',
      // initLocked: true,                    // initial locked state
      // lockedOnEmpty: false,                // Should the field remain locked on empty
      igaCss: 'input-group-append',           // tki-iga template class (BS4 data-iga-css="input-group-btn")
      // The input element is to be located into here and this into the input el position
      groupTpl: '<div class="input-group"><div class="tki-iga"><button type="button" title="Click to edit field" class="btn btn-default"><i class="fa"></i></button></div></div>'
    };
    var $element = $(element);
    var plugin = this;
    plugin.settings = {};
    var form = null;

    // constructor method
    plugin.init = function () {
      plugin.settings = $.extend({}, defaults, $(element).data(), options);
      form = $(element).closest('form');

      var group = $(plugin.settings.groupTpl);
      group.find('.tki-iga').addClass(plugin.settings.igaCss);


      //$element.parent().prepend(group);
      group.insertBefore($element);

      $element.detach();
      group.prepend($element);

      group.find('button').on('click', function () {
        $(this).blur();
        if (group.find('input').attr('readonly')) {
          //if (confirm('Are you sure you want to edit this field?')) {
          group.find('button .fa').removeClass(plugin.settings.lockIcon).addClass(plugin.settings.unlockIcon);
          group.find('input').removeAttr('readonly');
          //}
        }

      });

      $element.on('change', function () {
        updateInput(group);
      });
      updateInput(group);


    };  // END init()

    /**
     * @param group
     */
    var updateInput = function(group) {
      if (group.find('input').val()) {
        group.find('button .fa').removeClass(plugin.settings.unlockIcon).addClass(plugin.settings.lockIcon);
        group.find('input').attr('readonly', 'readonly');
      } else {
        group.find('button .fa').removeClass(plugin.settings.lockIcon).addClass(plugin.settings.unlockIcon);
        group.find('input').removeAttr('readonly');
      }
    };

    // private methods
    //var foo_private_method = function() { };

    // public methods
    //plugin.foo_public_method = function() { };

    // call the "constructor" method
    plugin.init();
  };

  // add the plugin to the jQuery.fn object
  $.fn.tkInputLock = function (options) {
    return this.each(function () {
      if (undefined === $(this).data('tkInputLock')) {
        var plugin = new tkInputLock(this, options);
        $(this).data('tkInputLock', plugin);
      }
    });
  }

})(jQuery);
