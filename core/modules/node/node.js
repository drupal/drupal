/**
 * @file
 * Defines Javascript behaviors for the node module.
 */

(function ($, Drupal, drupalSettings) {

  "use strict";

  Drupal.behaviors.nodeDetailsSummaries = {
    attach: function (context) {
      var $context = $(context);
      $context.find('.node-form-revision-information').drupalSetSummary(function (context) {
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

      $context.find('.node-form-author').drupalSetSummary(function (context) {
        var $context = $(context);
        var name = $context.find('.form-item-name input').val() || drupalSettings.anonymous,
          date = $context.find('.form-item-date input').val();
        return date ?
          Drupal.t('By @name on @date', { '@name': name, '@date': date }) :
          Drupal.t('By @name', { '@name': name });
      });

      $context.find('.node-form-options').drupalSetSummary(function (context) {
        var $context = $(context);
        var vals = [];

        if ($context.find('input').is(':checked')) {
          $context.find('input:checked').parent().each(function () {
            vals.push(Drupal.checkPlain($.trim($(this).text())));
          });
          return vals.join(', ');
        }
        else {
          return Drupal.t('Not promoted');
        }
      });

      $context.find('fieldset.node-translation-options').drupalSetSummary(function (context) {
        var $context = $(context);
        var translate;
        var $checkbox = $context.find('.form-item-translation-translate input');

        if ($checkbox.size()) {
          translate = $checkbox.is(':checked') ? Drupal.t('Needs to be updated') : Drupal.t('Does not need to be updated');
        }
        else {
          $checkbox = $context.find('.form-item-translation-retranslate input');
          translate = $checkbox.is(':checked') ? Drupal.t('Flag other translations as outdated') : Drupal.t('Do not flag other translations as outdated');
        }

        return translate;
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
