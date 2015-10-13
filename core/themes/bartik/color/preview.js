/**
 * @file
 * Preview for the Bartik theme.
 */
(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.color = {
    logoChanged: false,
    callback: function (context, settings, form, farb, height, width) {
      // Change the logo to be the real one.
      if (!this.logoChanged) {
        $('.color-preview .color-preview-logo img').attr('src', drupalSettings.color.logo);
        this.logoChanged = true;
      }
      // Remove the logo if the setting is toggled off.
      if (drupalSettings.color.logo === null) {
        $('div').remove('.color-preview-logo');
      }

      // Solid background.
      form.find('.color-preview').css('backgroundColor', $('.js-color-palette input[name="palette[bg]"]').val());

      // Text preview.
      form.find('.color-preview .color-preview-main h2, .color-preview .preview-content').css('color', form.find('.js-color-palette input[name="palette[text]"]').val());
      form.find('.color-preview .color-preview-content a').css('color', form.find('.js-color-palette input[name="palette[link]"]').val());

      // Sidebar block.
      form.find('.color-preview .color-preview-sidebar .color-preview-block').css('background-color', form.find('.js-color-palette input[name="palette[sidebar]"]').val());
      form.find('.color-preview .color-preview-sidebar .color-preview-block').css('border-color', form.find('.js-color-palette input[name="palette[sidebarborders]"]').val());

      // Footer wrapper background.
      form.find('.color-preview .color-preview-footer-wrapper', form).css('background-color', form.find('.js-color-palette input[name="palette[footer]"]').val());

      // CSS3 Gradients.
      var gradient_start = form.find('.js-color-palette input[name="palette[top]"]').val();
      var gradient_end = form.find('.js-color-palette input[name="palette[bottom]"]').val();

      form.find('.color-preview .color-preview-header').attr('style', 'background-color: ' + gradient_start + '; background-image: -webkit-gradient(linear, 0% 0%, 0% 100%, from(' + gradient_start + '), to(' + gradient_end + ')); background-image: -moz-linear-gradient(-90deg, ' + gradient_start + ', ' + gradient_end + ');');

      form.find('.color-preview .color-preview-site-name').css('color', form.find('.js-color-palette input[name="palette[titleslogan]"]').val());
    }
  };
})(jQuery, Drupal, drupalSettings);
