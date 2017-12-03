/**
 * @file
 * Preview for the Bartik theme.
 */
(function ($, Drupal, drupalSettings) {
  Drupal.color = {
    logoChanged: false,
    callback(context, settings, $form) {
      // Change the logo to be the real one.
      if (!this.logoChanged) {
        $('.color-preview .color-preview-logo img').attr('src', drupalSettings.color.logo);
        this.logoChanged = true;
      }
      // Remove the logo if the setting is toggled off.
      if (drupalSettings.color.logo === null) {
        $('div').remove('.color-preview-logo');
      }

      const $colorPreview = $form.find('.color-preview');
      const $colorPalette = $form.find('.js-color-palette');

      // Solid background.
      $colorPreview.css('backgroundColor', $colorPalette.find('input[name="palette[bg]"]').val());

      // Text preview.
      $colorPreview.find('.color-preview-main h2, .color-preview .preview-content').css('color', $colorPalette.find('input[name="palette[text]"]').val());
      $colorPreview.find('.color-preview-content a').css('color', $colorPalette.find('input[name="palette[link]"]').val());

      // Sidebar block.
      const $colorPreviewBlock = $colorPreview.find('.color-preview-sidebar .color-preview-block');
      $colorPreviewBlock.css('background-color', $colorPalette.find('input[name="palette[sidebar]"]').val());
      $colorPreviewBlock.css('border-color', $colorPalette.find('input[name="palette[sidebarborders]"]').val());

      // Footer wrapper background.
      $colorPreview.find('.color-preview-footer-wrapper').css('background-color', $colorPalette.find('input[name="palette[footer]"]').val());

      // CSS3 Gradients.
      const gradientStart = $colorPalette.find('input[name="palette[top]"]').val();
      const gradientEnd = $colorPalette.find('input[name="palette[bottom]"]').val();

      $colorPreview.find('.color-preview-header').attr('style', `background-color: ${gradientStart}; background-image: -webkit-gradient(linear, 0% 0%, 0% 100%, from(${gradientStart}), to(${gradientEnd})); background-image: -moz-linear-gradient(-90deg, ${gradientStart}, ${gradientEnd});`);

      $colorPreview.find('.color-preview-site-name').css('color', $colorPalette.find('input[name="palette[titleslogan]"]').val());
    },
  };
}(jQuery, Drupal, drupalSettings));
