// $Id: system.js,v 1.3 2007/04/29 16:06:04 dries Exp $

/**
 * Internal function to check using Ajax if clean URLs can be enabled on the
 * settings page.
 *
 * This function is not used to verify whether or not clean URLs
 * are currently enabled.
 */
Drupal.cleanURLsSettingsCheck = function() {
  var url = location.pathname +"admin/settings/clean-urls";
  $("#clean-url .description span").html('<div id="testing">'+ Drupal.settings.cleanURL.testing +"</div>");
  $("#clean-url p").hide();
  $.ajax({url: location.protocol +"//"+ location.host + url, type: "GET", data: " ", complete: function(response) {
    $("#testing").toggle();
    if (response.status == 200) {
      // Check was successful.
      $("#clean-url input.form-radio").attr("disabled", "");
      $("#clean-url .description span").append('<div class="ok">'+ Drupal.settings.cleanURL.success +"</div>");
    }
    else {
      // Check failed.
      $("#clean-url .description span").append('<div class="warning">'+ Drupal.settings.cleanURL.failure +"</div>");
    }
  }});
}
