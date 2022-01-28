(function ($, Drupal) {
  /**
   * Provides the summary for the "language" plugin settings vertical tab.
   */
  Drupal.behaviors.ckeditorLanguageSettingsSummary = {
    attach() {
      $('#edit-editor-settings-plugins-language').drupalSetSummary(
        (context) => {
          const $selected = $(
            '#edit-editor-settings-plugins-language-language-list-type option:selected',
          );
          if ($selected.length) {
            return $selected[0].textContent;
          }
          return '';
        },
      );
    },
  };
})(jQuery, Drupal);
