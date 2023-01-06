/**
 * @file
 * CKEditor 5 Image admin behavior.
 */

(function ($, Drupal) {
  /**
   * Provides the summary for the "image" plugin settings vertical tab.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches summary behavior to the plugin settings vertical tab.
   */
  Drupal.behaviors.ckeditor5ImageSettingsSummary = {
    attach() {
      $('[data-ckeditor5-plugin-id="ckeditor5_image"]').drupalSetSummary(
        (context) => {
          const uploadsEnabled = document.querySelector(
            '[data-drupal-selector="edit-editor-settings-plugins-ckeditor5-image-status"]',
          ).checked;
          if (uploadsEnabled) {
            return Drupal.t('Images can only be uploaded.');
          }
          return Drupal.t('Images can only be added by URL.');
        },
      );
    },
  };
})(jQuery, Drupal);
