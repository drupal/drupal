// $Id$

(function ($) {

Drupal.behaviors.menuFieldsetSummaries = {
  attach: function (context) {
    $('fieldset#edit-menu', context).setSummary(function (context) {
      return Drupal.checkPlain($('#edit-menu-link-title', context).val()) || Drupal.t('Not in menu');
    });
  }
};

})(jQuery);
