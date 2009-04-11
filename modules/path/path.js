// $Id: path.js,v 1.1 2009/04/11 22:19:45 webchick Exp $

(function($) {

Drupal.behaviors.pathFieldsetSummaries = {
  attach: function(context) {
    $('fieldset#edit-path', context).setSummary(function(context) {
      var path = $('#edit-path-1').val();

      return path ?
        Drupal.t('Alias: @alias', { '@alias': path }) :
        Drupal.t('No alias');
    });
  }
};

})(jQuery);
