// $Id$

(function ($) {

  Drupal.behaviors.menuChangeParentItems = {
    attach: function (context, settings) {
      $('fieldset#edit-menu input').each(function () {
        $(this).change(function () {
          // Update list of available parent menu items.
          Drupal.menu_update_parent_list();
        });
      });
    }
  }

  /**
   * Function to set the options of the menu parent item dropdown.
   */
  Drupal.menu_update_parent_list = function () {
    var values = [];

    $('input:checked', $('fieldset#edit-menu')).each(function () {
      // Get the names of all checked menus.
      values.push(Drupal.checkPlain($.trim($(this).val())));
    });

    var url = Drupal.settings.basePath + 'admin/structure/menu/parents';
    $.ajax({
      url: location.protocol + '//' + location.host + url,
      type: 'POST',
      data: {'menus[]' : values},
      dataType: 'json',
      success: function (options) {
        // Save key of last selected element.
        var selected = $('fieldset#edit-menu #edit-menu-parent :selected').val();
        // Remove all exisiting options from dropdown.
        $('fieldset#edit-menu #edit-menu-parent').children().remove();
        // Add new options to dropdown.
        jQuery.each(options, function(index, value) {
          $('fieldset#edit-menu #edit-menu-parent').append(
            $('<option ' + (index == selected ? ' selected="selected"' : '') + '></option>').val(index).text(value)
          );
        });
      }
    });
  }

})(jQuery);
