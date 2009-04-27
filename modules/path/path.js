// $Id: path.js,v 1.2 2009/04/27 20:19:37 webchick Exp $

(function ($) {

Drupal.behaviors.pathFieldsetSummaries = {
  attach: function (context) {
    $('fieldset#edit-path', context).setSummary(function (context) {
      var path = $('#edit-path-1').val();

      return path ?
        Drupal.t('Alias: @alias', { '@alias': path }) :
        Drupal.t('No alias');
    });
  }
};

})(jQuery);
