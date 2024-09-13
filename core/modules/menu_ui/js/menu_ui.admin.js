/**
 * @file
 * Menu UI admin behaviors.
 */

(function ($, Drupal) {
  /**
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.menuUiChangeParentItems = {
    attach(context, settings) {
      const menu = once('menu-parent', '#edit-menu');
      if (menu.length) {
        const $menu = $(menu);
        // Update the list of available parent menu items to match the initial
        // available menus.
        Drupal.menuUiUpdateParentList();

        // Update list of available parent menu items.
        $menu.on('change', 'input', Drupal.menuUiUpdateParentList);
      }
    },
  };

  /**
   * Function to set the options of the menu parent item dropdown.
   */
  Drupal.menuUiUpdateParentList = function () {
    const $menu = $('#edit-menu');
    const values = [];

    $menu.find('input:checked').each(function () {
      // Get the names of all checked menus.
      values.push(Drupal.checkPlain(this.value));
    });

    $.ajax({
      url: `${window.location.protocol}//${window.location.host}${Drupal.url(
        'admin/structure/menu/parents',
      )}`,
      type: 'POST',
      data: { 'menus[]': values },
      dataType: 'json',
      success(options) {
        const $select = $('#edit-menu-parent');
        // Save key of last selected element.
        const selected = $select[0].value;
        // Remove all existing options from dropdown.
        $select.children().remove();
        // Add new options to dropdown. Keep a count of options for testing later.
        let totalOptions = 0;
        Object.keys(options || {}).forEach((machineName) => {
          const selectContents = document.createElement('option');
          selectContents.selected = machineName === selected;
          selectContents.value = machineName;
          selectContents.textContent = options[machineName];
          $select.append(selectContents);
          totalOptions++;
        });

        // Hide the parent options if there are no options for it.
        $select
          .closest('div')
          .toggle(totalOptions > 0)
          .attr('hidden', totalOptions === 0);
      },
    });
  };
})(jQuery, Drupal);
