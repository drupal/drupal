(function ($) {

"use strict";

/**
 * Makes field translatability inherit bundle translatability.
 */
Drupal.behaviors.translationEntity = {
  attach: function (context) {
    // Initially hide all field rows for non translatable bundles and all column
    // rows for non translatable fields.
    $(context).find('table .bundle-settings .translatable :input').once('translation-entity-admin-hide', function () {
      var $input = $(this);
      var $bundleSettings = $input.closest('.bundle-settings');
      if (!$input.is(':checked')) {
        $bundleSettings.nextUntil('.bundle-settings').hide();
      }
      else {
        $bundleSettings.nextUntil('.bundle-settings', '.field-settings').find('.translatable :input:not(:checked)').closest('.field-settings').nextUntil(':not(.column-settings)').hide();
      }
    });

    // When a bundle is made translatable all of its field instances should
    // inherit this setting. Instead when it is made non translatable its field
    // instances are hidden, since their translatability no longer matters.
    $('body').once('translation-entity-admin-bind').on('click', 'table .bundle-settings .translatable :input', function (e) {
      var $target = $(e.target);
      var $bundleSettings = $target.closest('.bundle-settings');
      var $settings = $bundleSettings.nextUntil('.bundle-settings');
      var $fieldSettings = $settings.filter('.field-settings');
      if ($target.is(':checked')) {
        $bundleSettings.find('.operations :input[name$="[language_show]"]').attr('checked', true);
        $fieldSettings.find('.translatable :input').attr('checked', true);
        $settings.show();
      }
      else {
        $settings.hide();
      }
    }).on('click', 'table .field-settings .translatable :input', function (e) {
      var $target = $(e.target);
      var $fieldSettings = $target.closest('.field-settings');
      var $columnSettings = $fieldSettings.nextUntil('.field-settings, .bundle-settings');
      if ($target.is(':checked')) {
        $columnSettings.show();
      }
      else {
        $columnSettings.hide();
      }
    });
  }
};

})(jQuery);
