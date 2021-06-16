/**
 * @file
 * Locale behavior.
 */

(function ($, Drupal) {
  /**
   * Select the language code of an imported file based on its filename.
   *
   * This only works if the file name ends with "LANGCODE.po".
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches behavior for preselecting language code based on filename.
   */
  Drupal.behaviors.importLanguageCodeSelector = {
    attach(context, settings) {
      const $form = $('#locale-translate-import-form').once('autodetect-lang');
      if ($form.length) {
        const $langcode = $form.find('.langcode-input');
        $form.find('.file-import-input').on('change', function () {
          // If the filename is fully the language code or the filename
          // ends with a language code, pre-select that one.
          const matches = $(this)
            .val()
            .match(/([^.][.]*)([\w-]+)\.po$/);
          if (
            matches &&
            $langcode.find(`option[value="${matches[2]}"]`).length
          ) {
            $langcode.val(matches[2]);
          }
        });
      }
    },
  };
})(jQuery, Drupal);
