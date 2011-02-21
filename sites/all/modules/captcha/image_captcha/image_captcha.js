// $Id: image_captcha.js,v 1.3 2010/11/20 00:42:32 soxofaan Exp $

(function($) {

  Drupal.behaviors.captchaAdmin = {
    attach : function(context) {

      // Helper function to show/hide noise level widget.
      var noise_level_shower = function(speed) {
        speed = (typeof speed == 'undefined') ? 'slow' : speed;
        if ($("#edit-image-captcha-dot-noise").is(":checked")
            || $("#edit-image-captcha-line-noise").is(":checked")) {
          $(".form-item-image-captcha-noise-level").show(speed);
        } else {
          $(".form-item-image-captcha-noise-level").hide(speed);
        }
      }
      // Add onclick handler to the dot and line noise check boxes.
      $("#edit-image-captcha-dot-noise").click(noise_level_shower);
      $("#edit-image-captcha-line-noise").click(noise_level_shower);
      // Show or hide appropriately on page load.
      noise_level_shower(0);

      // Helper function to show/hide smooth distortion widget.
      var smooth_distortion_shower = function(speed) {
        speed = (typeof speed == 'undefined') ? 'slow' : speed;
        if ($("#edit-image-captcha-distortion-amplitude").val() > 0) {
          $(".form-item-image-captcha-bilinear-interpolation").show(speed);
        } else {
          $(".form-item-image-captcha-bilinear-interpolation").hide(speed);
        }
      }
      // Add onchange handler to the distortion level select widget.
      $("#edit-image-captcha-distortion-amplitude").change(
          smooth_distortion_shower);
      // Show or hide appropriately on page load.
      smooth_distortion_shower(0)

    }
  };

})(jQuery);
