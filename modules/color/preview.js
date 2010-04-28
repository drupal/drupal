// $Id: preview.js,v 1.2 2010/04/28 20:25:21 dries Exp $

(function ($) {
  Drupal.color = {
    callback: function(context, settings, form, farb, height, width) {
      // Solid background.
      $('#preview', form).css('backgroundColor', $('#palette input[name="palette[base]"]', form).val());

      // Text preview
      $('#text', form).css('color', $('#palette input[name="palette[text]"]', form).val());
      $('#text a, #text h2', form).css('color', $('#palette input[name="palette[link]"]', form).val());

      // Set up gradients if there are some.
      var color_start, color_end;
      for (i in settings.gradients) {
        color_start = farb.unpack($('#palette input[name="palette[' + settings.gradients[i]['colors'][0] + ']"]', form).val());
        color_end = farb.unpack($('#palette input[name="palette[' + settings.gradients[i]['colors'][1] + ']"]', form).val());
        if (color_start && color_end) {
          var delta = [];
          for (j in color_start) {
            delta[j] = (color_end[j] - color_start[j]) / (settings.gradients[i]['vertical'] ? height[i] : width[i]);
          }
          var accum = color_start;
          // Render gradient lines.
          $('#gradient-' + i + ' > div', form).each(function () {
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
