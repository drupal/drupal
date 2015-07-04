/**
 * @file
 * Attaches preview-related behavior for the Color module.
 */

(function ($) {

  "use strict";

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
    callback: function (context, settings, form, farb, height, width) {
      var accum;
      var delta;
      // Solid background.
      form.find('.color-preview').css('backgroundColor', form.find('.color-palette input[name="palette[base]"]').val());

      // Text preview.
      form.find('#text').css('color', form.find('.color-palette input[name="palette[text]"]').val());
      form.find('#text a, #text h2').css('color', form.find('.color-palette input[name="palette[link]"]').val());

      function gradientLineColor(i, element) {
        for (var k in accum) {
          if (accum.hasOwnProperty(k)) {
            accum[k] += delta[k];
          }
        }
        element.style.backgroundColor = farb.pack(accum);
      }

      // Set up gradients if there are some.
      var color_start;
      var color_end;
      for (var i in settings.gradients) {
        if (settings.gradients.hasOwnProperty(i)) {
          color_start = farb.unpack(form.find('.color-palette input[name="palette[' + settings.gradients[i].colors[0] + ']"]').val());
          color_end = farb.unpack(form.find('.color-palette input[name="palette[' + settings.gradients[i].colors[1] + ']"]').val());
          if (color_start && color_end) {
            delta = [];
            for (var j in color_start) {
              if (color_start.hasOwnProperty(j)) {
                delta[j] = (color_end[j] - color_start[j]) / (settings.gradients[i].vertical ? height[i] : width[i]);
              }
            }
            accum = color_start;
            // Render gradient lines.
            form.find('#gradient-' + i + ' > div').each(gradientLineColor);
          }
        }
      }
    }
  };
})(jQuery);
