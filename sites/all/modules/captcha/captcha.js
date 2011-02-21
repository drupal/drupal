// $Id: captcha.js,v 1.4 2010/02/20 16:57:41 soxofaan Exp $

(function ($) {

Drupal.behaviors.captchaAdmin = {
  attach: function(context) {
  	// Add onclick handler to checkbox for adding a CAPTCHA description
  	// so that the textfields for the CAPTCHA description are hidden
  	// when no description should be added.
    // @todo: div.form-item-captcha-description depends on theming, maybe
    // it's better to add our own wrapper with id (instead of a class).
  	$("#edit-captcha-add-captcha-description").click(function() {
  		if ($("#edit-captcha-add-captcha-description").is(":checked")) {
  			// Show the CAPTCHA description textfield(s).
  			$("div.form-item-captcha-description").show('slow');
  		}
  		else {
  			// Hide the CAPTCHA description textfield(s).
  			$("div.form-item-captcha-description").hide('slow');
  		}
  	});
  	// Hide the CAPTCHA description textfields if option is disabled on page load.
  	if (!$("#edit-captcha-add-captcha-description").is(":checked")) {
  		$("div.form-item-captcha-description").hide();
  	}
  }

};

})(jQuery);
