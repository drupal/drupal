/**
 * @file
 * User permission page behaviors.
 */

(function ($) {

  "use strict";

  /**
   * Shows checked and disabled checkboxes for inherited permissions.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.permissions = {
    attach: function (context) {
      var self = this;
      $('table#permissions').once('permissions').each(function () {
        // On a site with many roles and permissions, this behavior initially has
        // to perform thousands of DOM manipulations to inject checkboxes and hide
        // them. By detaching the table from the DOM, all operations can be
        // performed without triggering internal layout and re-rendering processes
        // in the browser.
        var $table = $(this);
        var $ancestor;
        var method;
        if ($table.prev().length) {
          $ancestor = $table.prev();
          method = 'after';
        }
        else {
          $ancestor = $table.parent();
          method = 'append';
        }
        $table.detach();

        // Create dummy checkboxes. We use dummy checkboxes instead of reusing
        // the existing checkboxes here because new checkboxes don't alter the
        // submitted form. If we'd automatically check existing checkboxes, the
        // permission table would be polluted with redundant entries. This
        // is deliberate, but desirable when we automatically check them.
        var $dummy = $('<input type="checkbox" class="dummy-checkbox js-dummy-checkbox" disabled="disabled" checked="checked" />')
          .attr('title', Drupal.t("This permission is inherited from the authenticated user role."))
          .hide();

        $table
          .find('input[type="checkbox"]')
          .not('.js-rid-anonymous, .js-rid-authenticated')
          .addClass('real-checkbox js-real-checkbox')
          .after($dummy);

        // Initialize the authenticated user checkbox.
        $table.find('input[type=checkbox].js-rid-authenticated')
          .on('click.permissions', self.toggle)
          // .triggerHandler() cannot be used here, as it only affects the first
          // element.
          .each(self.toggle);

        // Re-insert the table into the DOM.
        $ancestor[method]($table);
      });
    },

    /**
     * Toggles all dummy checkboxes based on the checkboxes' state.
     *
     * If the "authenticated user" checkbox is checked, the checked and disabled
     * checkboxes are shown, the real checkboxes otherwise.
     */
    toggle: function () {
      var authCheckbox = this;
      var $row = $(this).closest('tr');
      // jQuery performs too many layout calculations for .hide() and .show(),
      // leading to a major page rendering lag on sites with many roles and
      // permissions. Therefore, we toggle visibility directly.
      $row.find('.js-real-checkbox').each(function () {
        this.style.display = (authCheckbox.checked ? 'none' : '');
      });
      $row.find('.js-dummy-checkbox').each(function () {
        this.style.display = (authCheckbox.checked ? '' : 'none');
      });
    }
  };

})(jQuery);
