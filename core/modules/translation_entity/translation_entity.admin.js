(function ($) {

"use strict";

/**
 * Makes field translatability inherit bundle translatability.
 */
Drupal.behaviors.translationEntity = {
  attach: function (context) {
    var $context = $(context);
    // Initially hide all field rows for non translatable bundles.
    var $inputs = $context.find('table .bundle-settings .translatable :input');
    $inputs.filter(':not(:checked)').once('translation-entity-admin-hide', function () {
      $(this).closest('.bundle-settings').nextUntil('.bundle-settings').hide();
    });

    // When a bundle is made translatable all of its field instances should
    // inherit this setting. Instead when it is made non translatable its field
    // instances are hidden, since their translatability no longer matters.
    $('body').once('translation-entity-admin-bind').on('click', 'table .bundle-settings .translatable :input', function (e) {
      var $target = $(e.target);
      var $bundleSettings = $target.closest('.bundle-settings');
      var $fieldSettings = $bundleSettings.nextUntil('.bundle-settings');
      if ($target.is(':checked')) {
        $bundleSettings.find('.operations :input[name$="[language_hidden]"]').attr('checked', false);
        $fieldSettings.find('.translatable :input').attr('checked', true);
        $fieldSettings.show();
      }
      else {
        $fieldSettings.hide();
      }
    });
  }
};

})(jQuery);
