(function ($, Drupal) {
  /**
   * Provides the summary for the "language" plugin settings vertical tab.
   */
  Drupal.behaviors.ckeditorLanguageSettingsSummary = {
    attach() {
      $('#edit-editor-settings-plugins-language').drupalSetSummary(context => $('#edit-editor-settings-plugins-language-language-list-type option:selected').text());
    },
  };
}(jQuery, Drupal));
