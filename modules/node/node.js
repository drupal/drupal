// $Id: node.js,v 1.4 2009/04/27 20:19:37 webchick Exp $

(function ($) {

Drupal.behaviors.nodeFieldsetSummaries = {
  attach: function (context) {
    $('fieldset#edit-revision-information', context).setSummary(function (context) {
      return $('#edit-revision', context).is(':checked') ?
        Drupal.t('New revision') :
        Drupal.t('No revision');
    });

    $('fieldset#edit-author', context).setSummary(function (context) {
      var name = $('#edit-name').val(), date = $('#edit-date').val();
      return date ?
        Drupal.t('By @name on @date', { '@name': name, '@date': date }) :
        Drupal.t('By @name', { '@name': name });
    });

    $('fieldset#edit-options', context).setSummary(function (context) {
      var vals = [];

      $('input:checked', context).parent().each(function () {
        vals.push(Drupal.checkPlain($.trim($(this).text())));
      });

      if (!$('#edit-status', context).is(':checked')) {
        vals.unshift(Drupal.t('Not published'));
      }
      return vals.join(', ');
    });
  }
};

})(jQuery);
