/**
 * @file
 * Menu UI admin behaviors.
 */

(function ($, Drupal) {

  'use strict';

  /**
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.menuUiChangeParentItems = {
    attach: function (context, settings) {
      var $menu = $('#edit-menu').once('menu-parent');
      if ($menu.length) {
        // Update the list of available parent menu items to match the initial
        // available menus.
        Drupal.menuUiUpdateParentList();

        // Update list of available parent menu items.
        $menu.on('change', 'input', Drupal.menuUiUpdateParentList);
      }
    }
  };

  /**
   * Function to set the options of the menu parent item dropdown.
   */
  Drupal.menuUiUpdateParentList = function () {
    var $menu = $('#edit-menu');
    var values = [];

    $menu.find('input:checked').each(function () {
      // Get the names of all checked menus.
      values.push(Drupal.checkPlain($.trim($(this).val())));
    });

    $.ajax({
      url: location.protocol + '//' + location.host + Drupal.url('admin/structure/menu/parents'),
      type: 'POST',
      data: {'menus[]': values},
      dataType: 'json',
      success: function (options) {
        var $select = $('#edit-menu-parent');
        // Save key of last selected element.
        var selected = $select.val();
        // Remove all existing options from dropdown.
        $select.children().remove();
        // Add new options to dropdown. Keep a count of options for testing later.
        var totalOptions = 0;
        for (var machineName in options) {
          if (options.hasOwnProperty(machineName)) {
            $select.append(
              $('<option ' + (machineName === selected ? ' selected="selected"' : '') + '></option>').val(machineName).text(options[machineName])
            );
            totalOptions++;
          }
        }

        // Hide the parent options if there are no options for it.
        $select.closest('div').toggle(totalOptions > 0).attr('hidden', totalOptions === 0);
      }
    });
  };

})(jQuery, Drupal);
