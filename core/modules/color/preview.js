/**
 * @file
 * Attaches preview-related behavior for the Color module.
 */

(function ($) {

  "use strict";

  /**
   * @namespace
   */
  Drupal.color = {

    /**
     * @param {Element} context
     * @param {object} settings
     * @param {HTMLFormElement} form
     * @param {object} farb
     * @param {number} height
     * @param {number} width
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
