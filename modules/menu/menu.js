// $Id: menu.js,v 1.1 2009/04/11 22:19:45 webchick Exp $

(function($) {

Drupal.behaviors.menuFieldsetSummaries = {
  attach: function(context) {
    $('fieldset#edit-menu', context).setSummary(function(context) {
      return Drupal.checkPlain($('#edit-menu-link-title', context).val()) || Drupal.t('Not in menu');
    });
  }
};

})(jQuery);
