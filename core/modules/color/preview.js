/**
 * @file
 * Attaches preview-related behavior for the Color module.
 */

(function ($) {

  "use strict";

  Drupal.color = {
    callback: function(context, settings, form, farb, height, width) {
      // Solid background.
      form.find('#preview').css('backgroundColor', form.find('#palette input[name="palette[base]"]').val());

      // Text preview
      form.find('#text').css('color', form.find('#palette input[name="palette[text]"]').val());
      form.find('#text a, #text h2').css('color', form.find('#palette input[name="palette[link]"]').val());

      // Set up gradients if there are some.
      var color_start, color_end;
      for (i in settings.gradients) {
        color_start = farb.unpack(form.find('#palette input[name="palette[' + settings.gradients[i]['colors'][0] + ']"]').val());
        color_end = farb.unpack(form.find('#palette input[name="palette[' + settings.gradients[i]['colors'][1] + ']"]').val());
        if (color_start && color_end) {
          var delta = [];
          for (j in color_start) {
            delta[j] = (color_end[j] - color_start[j]) / (settings.gradients[i]['vertical'] ? height[i] : width[i]);
          }
          var accum = color_start;
          // Render gradient lines.
          form.find('#gradient-' + i + ' > div').each(function () {
            for (j in accum) {
              accum[j] += delta[j];
            }
            this.style.backgroundColor = farb.pack(accum);
          });
        }
      }
    }
  };
})(jQuery);
