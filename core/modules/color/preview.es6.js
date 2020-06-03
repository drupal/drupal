/**
 * @file
 * Attaches preview-related behavior for the Color module.
 */

(function($, Drupal) {
  /**
   * Namespace for color-related functionality for Drupal.
   *
   * @namespace
   */
  Drupal.color = {
    /**
     * The callback for when the color preview has been attached.
     *
     * @param {Element} context
     *   The context to initiate the color behaviour.
     * @param {object} settings
     *   Settings for the color functionality.
     * @param {HTMLFormElement} form
     *   The form to initiate the color behaviour on.
     * @param {object} farb
     *   The farbtastic object.
     * @param {number} height
     *   Height of gradient.
     * @param {number} width
     *   Width of gradient.
     */
    callback(context, settings, form, farb, height, width) {
      let accum;
      let delta;
      // Solid background.
      form
        .find('.color-preview')
        .css(
          'backgroundColor',
          form.find('.color-palette input[name="palette[base]"]').val(),
        );

      // Text preview.
      form
        .find('#text')
        .css(
          'color',
          form.find('.color-palette input[name="palette[text]"]').val(),
        );
      form
        .find('#text a, #text h2')
        .css(
          'color',
          form.find('.color-palette input[name="palette[link]"]').val(),
        );

      function gradientLineColor(i, element) {
        Object.keys(accum || {}).forEach(k => {
          accum[k] += delta[k];
        });
        element.style.backgroundColor = farb.pack(accum);
      }

      // Set up gradients if there are some.
      let colorStart;
      let colorEnd;
      Object.keys(settings.gradients || {}).forEach(i => {
        colorStart = farb.unpack(
          form
            .find(
              `.color-palette input[name="palette[${settings.gradients[i].colors[0]}]"]`,
            )
            .val(),
        );
        colorEnd = farb.unpack(
          form
            .find(
              `.color-palette input[name="palette[${settings.gradients[i].colors[1]}]"]`,
            )
            .val(),
        );
        if (colorStart && colorEnd) {
          delta = [];
          Object.keys(colorStart || {}).forEach(colorStartKey => {
            delta[colorStartKey] =
              (colorEnd[colorStartKey] - colorStart[colorStartKey]) /
              (settings.gradients[i].vertical ? height[i] : width[i]);
          });
          accum = colorStart;
          // Render gradient lines.
          form.find(`#gradient-${i} > div`).each(gradientLineColor);
        }
      });
    },
  };
})(jQuery, Drupal);
