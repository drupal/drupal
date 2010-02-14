// $Id: menu.js,v 1.5 2010/02/14 09:39:45 dries Exp $

(function ($) {

Drupal.behaviors.menuFieldsetSummaries = {
  attach: function (context) {
    $('fieldset.menu-link-form', context).setSummary(function (context) {
      if ($('#edit-menu-enabled', context).attr('checked')) {
        return Drupal.checkPlain($('#edit-menu-link-title', context).val());
      }
      else {
        return Drupal.t('Not in menu');
      }
    });
  }
};

/**
 * Automatically fill in a menu link title, if possible.
 */
Drupal.behaviors.menuLinkAutomaticTitle = {
  attach: function (context) {
    // Try to find menu settings widget elements as well as a 'title' field in
    // the form, but play nicely with user permissions and form alterations.
    var $checkbox = $('fieldset.menu-link-form #edit-menu-enabled', context);
    var $link_title = $('#edit-menu-link-title', context);
    var $title = $('#edit-title', context);
    // Bail out if we do not have all required fields.
    if (!($checkbox.length && $link_title.length && $title.length)) {
      return;
    }
    // If there is a link title already, mark it as overridden. The user expects
    // that toggling the checkbox twice will take over the node's title.
    if ($checkbox.attr('checked') && $link_title.val().length) {
      $link_title.data('menuLinkAutomaticTitleOveridden', true);
    }
    // Whenever the value is changed manually, disable this behavior.
    $link_title.keyup(function () {
      $link_title.data('menuLinkAutomaticTitleOveridden', true);
    });
    // Global trigger on checkbox (do not fill-in a value when disabled).
    $checkbox.change(function () {
      if ($checkbox.attr('checked')) {
        if (!$link_title.data('menuLinkAutomaticTitleOveridden')) {
          $link_title.val($title.val());
        }
      }
      else {
        $link_title.val('');
        $link_title.removeData('menuLinkAutomaticTitleOveridden');
      }
      $checkbox.closest('fieldset.vertical-tabs-pane').trigger('summaryUpdated');
      $checkbox.trigger('formUpdated');
    });
    // Take over any title change.
    $title.keyup(function () {
      if (!$link_title.data('menuLinkAutomaticTitleOveridden') && $checkbox.attr('checked')) {
        $link_title.val($title.val());
        $link_title.val($title.val()).trigger('formUpdated');
      }
    });
  }
};

})(jQuery);
