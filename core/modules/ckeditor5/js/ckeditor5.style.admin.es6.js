/**
 * @file
 * CKEditor 5 Style admin behavior.
 */

(function ($, Drupal) {
  /**
   * Provides the summary for the "style" plugin settings vertical tab.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches summary behavior to the plugin settings vertical tab.
   */
  Drupal.behaviors.ckeditor5StyleSettingsSummary = {
    attach() {
      $('[data-ckeditor5-plugin-id="ckeditor5_style"]').drupalSetSummary(
        (context) => {
          const stylesElement = document.querySelector(
            '[data-drupal-selector="edit-editor-settings-plugins-ckeditor5-style-styles"]',
          );
          const styleCount = stylesElement.value
            .split('\n')
            // Minimum length is 5: "p.z|Z" is the shortest possible style definition.
            .filter((line) => line.trim().length >= 5).length;

          if (styleCount === 0) {
            return Drupal.t('No styles configured');
          }
          return Drupal.formatPlural(
            styleCount,
            'One style configured',
            '@count styles configured',
          );
        },
      );
    },
  };
})(jQuery, Drupal);
