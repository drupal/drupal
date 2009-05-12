// $Id: user.permissions.js,v 1.1 2009/05/12 08:33:19 dries Exp $
(function ($) {

/**
 * Shows checked and disabled checkboxes for inherited permissions.
 */
Drupal.behaviors.permissions = {
  attach: function (context) {
    $('table#permissions:not(.permissions-processed)').each(function () {
      // Create dummy checkboxes. We use dummy checkboxes instead of reusing
      // the existing checkboxes here because new checkboxes don't alter the
      // submitted form. If we'd automatically check existing checkboxes, the
      // permission table would be polluted with redundant entries. This
      // is deliberate, but desirable when we automatically check them.
      $(':checkbox', this).not('[name^="2["]').not('[name^="1["]').each(function () {
        $(this).addClass('real-checkbox');
        $('<input type="checkbox" class="dummy-checkbox" disabled="disabled" checked="checked" />')
          .attr('title', Drupal.t("This permission is inherited from the authenticated user role."))
          .hide()
          .insertAfter(this);
      });

      // Helper function toggles all dummy checkboxes based on the checkboxes'
      // state. If the "authenticated user" checkbox is checked, the checked
      // and disabled checkboxes are shown, the real checkboxes otherwise.
      var toggle = function () {
        $(this).closest('tr')
          .find('.real-checkbox')[this.checked ? 'hide' : 'show']().end()
          .find('.dummy-checkbox')[this.checked ? 'show' : 'hide']();
      };

      // Initialize the authenticated user checkbox.
      $(':checkbox[name^="2["]', this)
        .click(toggle)
        .each(function () { toggle.call(this); });
    }).addClass('permissions-processed');
  }
};

})(jQuery);
