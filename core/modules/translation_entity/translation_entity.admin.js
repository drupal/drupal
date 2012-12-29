(function ($) {

"use strict";

/**
 * Makes field translatability inherit bundle translatability.
 */
Drupal.behaviors.translationEntity = {
  attach: function (context) {
    // Initially hide all field rows for non translatable bundles.
    var $input = $('table .bundle-settings .translatable :input', context);
    $input.filter(':not(:checked)').once('translation-entity-admin-hide', function() {
      $(this).closest('.bundle-settings').nextUntil('.bundle-settings').hide();
    });

    // When a bundle is made translatable all of its field instances should
    // inherit this setting. Instead when it is made non translatable its field
    // instances are hidden, since their translatability no longer matters.
    $input.once('translation-entity-admin-bind', function() {
      var $bundleTranslatable = $(this).click(function() {
        var $bundleSettings = $bundleTranslatable.closest('.bundle-settings');
        var $fieldSettings = $bundleSettings.nextUntil('.bundle-settings');
        if ($bundleTranslatable.is(':checked')) {
          $bundleSettings.find('.operations :input[name$="[language_hidden]"]').attr('checked', false);
          $fieldSettings.find('.translatable :input').attr('checked', true);
          $fieldSettings.show();
        }
        else {
          $fieldSettings.hide();
        }
      });
    });
  }
};

})(jQuery);
