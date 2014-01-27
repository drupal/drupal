(function ($, Drupal) {

  "use strict";

  /**
   * Select the language code of an imported file based on its filename.
   *
   * This only works if the file name ends with "LANGCODE.po".
   */
  Drupal.behaviors.importLanguageCodeSelector = {
    attach: function (context, settings) {
      var $form = $('#locale-translate-import-form').once('autodetect-lang');
      if ($form.length) {
        var $langcode = $form.find('.langcode-input');
        $form.find('.file-import-input')
          .on('change', function () {
            var matches = $(this).val().match(/\.([\-\w]+)\.po/);
            if (matches && $langcode.find('option[value="' + matches[1] + '"]').length) {
              $langcode.val(matches[1]);
            }
          });
      }
    }
  };

})(jQuery, Drupal);
