/**
 * @file
 * Preview for the Bartik theme.
 */
(function ($, Drupal, drupalSettings) {

  "use strict";

  Drupal.color = {
    logoChanged: false,
    callback: function (context, settings, form, farb, height, width) {
      // Change the logo to be the real one.
      if (!this.logoChanged) {
        $('#preview #preview-logo img').attr('src', drupalSettings.color.logo);
        this.logoChanged = true;
      }
      // Remove the logo if the setting is toggled off.
      if (drupalSettings.color.logo === null) {
        $('div').remove('#preview-logo');
      }

      // Solid background.
      form.find('#preview').css('backgroundColor', $('#palette input[name="palette[bg]"]').val());

      // Text preview.
      form.find('#preview #preview-main h2, #preview .preview-content').css('color', form.find('#palette input[name="palette[text]"]').val());
      form.find('#preview #preview-content a').css('color', form.find('#palette input[name="palette[link]"]').val());

      // Sidebar block.
      form.find('#preview #preview-sidebar #preview-block').css('background-color', form.find('#palette input[name="palette[sidebar]"]').val());
      form.find('#preview #preview-sidebar #preview-block').css('border-color', form.find('#palette input[name="palette[sidebarborders]"]').val());

      // Footer wrapper background.
      form.find('#preview #preview-footer-wrapper', form).css('background-color', form.find('#palette input[name="palette[footer]"]').val());

      // CSS3 Gradients.
      var gradient_start = form.find('#palette input[name="palette[top]"]').val();
      var gradient_end = form.find('#palette input[name="palette[bottom]"]').val();

      form.find('#preview #preview-header').attr('style', "background-color: " + gradient_start + "; background-image: -webkit-gradient(linear, 0% 0%, 0% 100%, from(" + gradient_start + "), to(" + gradient_end + ")); background-image: -moz-linear-gradient(-90deg, " + gradient_start + ", " + gradient_end + ");");

      form.find('#preview #preview-site-name').css('color', form.find('#palette input[name="palette[titleslogan]"]').val());
    }
  };
})(jQuery, Drupal, drupalSettings);
