// $Id: node.js,v 1.6 2010/05/04 16:03:34 dries Exp $

(function ($) {

Drupal.behaviors.nodeFieldsetSummaries = {
  attach: function (context) {
    $('fieldset#edit-revision-information', context).drupalSetSummary(function (context) {
      return $('#edit-revision', context).is(':checked') ?
        Drupal.t('New revision') :
        Drupal.t('No revision');
    });

    $('fieldset#edit-author', context).drupalSetSummary(function (context) {
      var name = $('#edit-name').val() || Drupal.settings.anonymous,
        date = $('#edit-date').val();
      return date ?
        Drupal.t('By @name on @date', { '@name': name, '@date': date }) :
        Drupal.t('By @name', { '@name': name });
    });

    $('fieldset#edit-options', context).drupalSetSummary(function (context) {
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
