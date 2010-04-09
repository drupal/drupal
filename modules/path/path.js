// $Id: path.js,v 1.4 2010/04/09 12:24:53 dries Exp $

(function ($) {

Drupal.behaviors.pathFieldsetSummaries = {
  attach: function (context) {
    $('fieldset#edit-path', context).drupalSetSummary(function (context) {
      var path = $('#edit-path-alias').val();

      return path ?
        Drupal.t('Alias: @alias', { '@alias': path }) :
        Drupal.t('No alias');
    });
  }
};

})(jQuery);
