// $Id: openid.js,v 1.5 2008/01/29 18:41:40 goba Exp $

Drupal.behaviors.openid = function (context) {
  var $loginElements = $("#edit-name-wrapper, #edit-pass-wrapper, li.openid-link");
  var $openidElements = $("#edit-openid-url-wrapper, li.user-link");

  // This behavior attaches by ID, so is only valid once on a page.
  if (!$("#edit-openid-url.openid-processed").size() && $("#edit-openid-url").val()) {
    $("#edit-openid-url").addClass('openid-processed');
    $loginElements.hide();
    // Use .css("display", "block") instead of .show() to be Konqueror friendly.
    $openidElements.css("display", "block");
  }
  $("li.openid-link:not(.openid-processed)", context)
    .addClass('openid-processed')
    .click( function() {
       $loginElements.hide();
       $openidElements.css("display", "block");
      // Remove possible error message.
      $("#edit-name, #edit-pass").removeClass("error");
      $("div.messages.error").hide();
      // Set focus on OpenID URL field.
      $("#edit-openid-url")[0].focus();
      return false;
    });
  $("li.user-link:not(.openid-processed)", context)
    .addClass('openid-processed')
    .click(function() {
       $openidElements.hide();
       $loginElements.css("display", "block");
      // Clear OpenID URL field and remove possible error message.
      $("#edit-openid-url").val('').removeClass("error");
      $("div.messages.error").css("display", "block");
      // Set focus on username field.
      $("#edit-name")[0].focus();
      return false;
    });
};
