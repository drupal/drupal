// $Id: system.js,v 1.6 2007/05/30 20:18:14 dries Exp $

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

/**
 * Internal function to check using Ajax if clean URLs can be enabled on the
 * install page.
 *
 * This function is not used to verify whether or not clean URLs
 * are currently enabled.
 */
Drupal.cleanURLsInstallCheck = function() {
  var pathname = location.pathname +"";
  var url = pathname.replace(/\/[^/]*$/, "/") +"node";
  $("#clean-url .description").append('<span><div id="testing">'+ Drupal.settings.cleanURL.testing +"</div></span>");
  $("#clean-url.install").css("display", "block");
  $.ajax({url: location.protocol +"//"+ location.host + url, type: "GET", data: " ", complete: function(response) {
    $("#testing").toggle();
    if (response.status == 200) {
      // Check was successful.
      $("#clean-url input.form-radio").attr("disabled", "");
      $("#clean-url .description span").append('<div class="ok">'+ Drupal.settings.cleanURL.success +"</div>");
      $("#clean-url input.form-radio").attr("checked", 1);
    }
    else {
      // Check failed.
      $("#clean-url .description span").append('<div class="warning">'+ Drupal.settings.cleanURL.failure +"</div>");
    }
  }});
}

Drupal.installDefaultTimezone = function() {
  var offset = new Date().getTimezoneOffset() * -60;
  $("#edit-date-default-timezone").val(offset);
}

/**
 * Show/hide custom format sections on the date-time settings page.
 */
Drupal.dateTimeAutoAttach = function() {
  // Show/hide custom format depending on the select's value.
  $("select.date-format").change(function() {
    $(this).parents("div.date-container").children("div.custom-container")[$(this).val() == "custom" ? "show" : "hide"]();
  });

  // Attach keyup handler to custom format inputs.
  $("input.custom-format").keyup(function() {
    var input = $(this);
    var url = Drupal.settings.dateTime.lookup +(Drupal.settings.dateTime.lookup.match(/\?q=/) ? "&format=" : "?format=") + Drupal.encodeURIComponent(input.val());
    $.getJSON(url, function(data) {
      $("div.description span", input.parent()).html(data);
    });
  });

  // Trigger the event handler to show the form input if necessary.
  $("select.date-format").trigger("change");
}
