// $Id$

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
