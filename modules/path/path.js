// $Id: path.js,v 1.3 2009/10/20 01:24:34 dries Exp $

(function ($) {

Drupal.behaviors.pathFieldsetSummaries = {
  attach: function (context) {
    $('fieldset#edit-path', context).setSummary(function (context) {
      var path = $('#edit-path-alias').val();

      return path ?
        Drupal.t('Alias: @alias', { '@alias': path }) :
        Drupal.t('No alias');
    });
  }
};

})(jQuery);
