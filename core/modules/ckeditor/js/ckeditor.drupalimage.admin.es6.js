/**
 * @file
 * CKEditor 'drupalimage' plugin admin behavior.
 */

(function ($, Drupal, drupalSettings) {
  /**
   * Provides the summary for the "drupalimage" plugin settings vertical tab.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches summary behavior to the "drupalimage" settings vertical tab.
   */
  Drupal.behaviors.ckeditorDrupalImageSettingsSummary = {
    attach() {
      $('[data-ckeditor-plugin-id="drupalimage"]').drupalSetSummary(
        (context) => {
          const root =
            'input[name="editor[settings][plugins][drupalimage][image_upload]';
          const $status = $(`${root}[status]"]`);
          const maxFileSizeElement = document.querySelector(
            `${root}[max_size]"]`,
          );
          const maxWidth = document.querySelector(
            `${root}[max_dimensions][width]"]`,
          );
          const maxHeight = document.querySelector(
            `${root}[max_dimensions][height]"]`,
          );
          const $scheme = $(`${root}[scheme]"]:checked`);

          const maxFileSize = maxFileSizeElement.value
            ? maxFileSizeElement.value
            : maxFileSizeElement.getAttribute('placeholder');
          const maxDimensions =
            maxWidth.value && maxHeight.value
              ? `(${maxWidth.value}x${maxHeight.value})`
              : '';

          if (!$status.is(':checked')) {
            return Drupal.t('Uploads disabled');
          }

          let output = '';
          output += Drupal.t('Uploads enabled, max size: @size @dimensions', {
            '@size': maxFileSize,
            '@dimensions': maxDimensions,
          });
          if ($scheme.length) {
            output += `<br />${$scheme.attr('data-label')}`;
          }
          return output;
        },
      );
    },
  };
})(jQuery, Drupal, drupalSettings);
