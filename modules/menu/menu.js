// $Id: menu.js,v 1.2 2009/04/27 20:19:36 webchick Exp $

(function ($) {

Drupal.behaviors.menuFieldsetSummaries = {
  attach: function (context) {
    $('fieldset#edit-menu', context).setSummary(function (context) {
      return Drupal.checkPlain($('#edit-menu-link-title', context).val()) || Drupal.t('Not in menu');
    });
  }
};

})(jQuery);
