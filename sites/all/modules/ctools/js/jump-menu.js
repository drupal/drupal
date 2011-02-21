// $Id: jump-menu.js,v 1.3 2010/10/11 22:18:22 sdboyer Exp $

(function($) {
  Drupal.behaviors.CToolsJumpMenu = { 
    attach: function(context) {
      $('.ctools-jump-menu-hide:not(.ctools-jump-menu-processed)')
        .addClass('ctools-jump-menu-processed')
        .hide();

      $('.ctools-jump-menu-change:not(.ctools-jump-menu-processed)')
        .addClass('ctools-jump-menu-processed')
        .change(function() {
          var loc = $(this).val();
          if (loc) {
            location.href = loc;
          }
          return false;
        });

      $('.ctools-jump-menu-button:not(.ctools-jump-menu-processed)')
        .addClass('ctools-jump-menu-processed')
        .click(function() {
          // Instead of submitting the form, just perform the redirect.

          // Find our sibling value.
          var $select = $(this).parents('form').find('.ctools-jump-menu-select');
          var loc = $select.val();
          if (loc) {
            location.href = loc;
          }
          return false;
        });
    }
  }
})(jQuery);
