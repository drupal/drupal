(function ($) {

"use strict";

Drupal.behaviors.nodeFieldsetSummaries = {
  attach: function (context) {
    var $context = $(context);
    $context.find('fieldset.node-form-revision-information').drupalSetSummary(function (context) {
      var $context = $(context);
      var revisionCheckbox = $context.find('.form-item-revision input');

      // Return 'New revision' if the 'Create new revision' checkbox is checked,
      // or if the checkbox doesn't exist, but the revision log does. For users
      // without the "Administer content" permission the checkbox won't appear,
      // but the revision log will if the content type is set to auto-revision.
      if (revisionCheckbox.is(':checked') || (!revisionCheckbox.length && $context.find('.form-item-log textarea').length)) {
        return Drupal.t('New revision');
      }

      return Drupal.t('No revision');
    });

    $context.find('fieldset.node-form-author').drupalSetSummary(function (context) {
      var $context = $(context);
      var name = $context.find('.form-item-name input').val() || Drupal.settings.anonymous,
        date = $context.find('.form-item-date input').val();
      return date ?
        Drupal.t('By @name on @date', { '@name': name, '@date': date }) :
        Drupal.t('By @name', { '@name': name });
    });

    $context.find('fieldset.node-form-options').drupalSetSummary(function (context) {
      var $context = $(context);
      var vals = [];

      $context.find('input:checked').parent().each(function () {
        vals.push(Drupal.checkPlain($.trim($(this).text())));
      });

      if (!$context.find('.form-item-status input').is(':checked')) {
        vals.unshift(Drupal.t('Not published'));
      }
      return vals.join(', ');
    });
  }
};

})(jQuery);
