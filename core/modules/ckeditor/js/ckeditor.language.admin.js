(function ($, Drupal) {

  'use strict';

  /**
   * Provides the summary for the "language" plugin settings vertical tab.
   */
  Drupal.behaviors.ckeditorLanguageSettingsSummary = {
    attach: function () {
      $('#edit-editor-settings-plugins-language').drupalSetSummary(function (context) {
        return $('#edit-editor-settings-plugins-language-language-list-type option:selected').text();
      });
    }
  };

})(jQuery, Drupal);
