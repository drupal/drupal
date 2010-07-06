/* $Id: preview.js,v 1.1 2010/07/06 05:25:51 webchick Exp $ */

(function ($) {
  Drupal.color = {
    logoChanged: false,
    callback: function(context, settings, form, farb, height, width) {
      // Change the logo to be the real one.
      if (!this.logoChanged) {
        $('#preview #preview-logo img').attr('src', Drupal.settings.color.logo);
        this.logoChanged = true;
      }

      // Solid background.
      $('#preview', form).css('backgroundColor', $('#palette input[name="palette[bg]"]', form).val());

      // Text preview.
      $('#preview #preview-main h2, #preview #preview-main p', form).css('color', $('#palette input[name="palette[text]"]', form).val());
      $('#preview #preview-content a', form).css('color', $('#palette input[name="palette[link]"]', form).val());

      // Sidebar background.
      $('#preview .sidebar .block', form).css('background-color', $('#palette input[name="palette[sidebar]"]', form).val());

      // Footer background.
      $('#preview #footer-wrapper', form).css('background-color', $('#palette input[name="palette[footer]"]', form).val());

      $('#preview .sidebar .block', form).css('border-color', $('#palette input[name="palette[sidebarborders]"]', form).val());

      // CSS3 Gradients.
      var gradient_start = $('#palette input[name="palette[top]"]', form).val();
      var gradient_end = $('#palette input[name="palette[bottom]"]', form).val();

      $('#preview #preview-header', form).attr('style', "background-color: " + gradient_start + "; background-image: -webkit-gradient(linear, 0% 0%, 0% 100%, from(" + gradient_start + "), to(" + gradient_end + ")); background-image: -moz-linear-gradient(-90deg, " + gradient_start + ", " + gradient_end + ");");

      $('#preview #preview-name-and-slogan a', form).css('color', $('#palette input[name="palette[titleslogan]"]', form).val());
    }
  };
})(jQuery);
