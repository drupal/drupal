// $Id: openid.js,v 1.2 2007/07/01 15:37:09 dries Exp $

Drupal.behaviors.openid = function (context) {
  // This behavior attaches by ID, so is only valid once on a page.
  if (!$("#edit-openid-url.openid-processed").size() && $("#edit-openid-url").val()) {
    $("#edit-openid-url").addClass('openid-processed');
    $("#edit-name-wrapper").hide();
    $("#edit-pass-wrapper").hide();
    $("#edit-openid-url-wrapper").show();
    $("a.openid-link").hide();
  }
  $("a.openid-link:not(.openid-processed)", context)
    .addClass('openid-processed')
    .click( function() {
      $("#edit-pass-wrapper").hide();
      $("#edit-name-wrapper").fadeOut('medium', function() {
          $("#edit-openid-url-wrapper").fadeIn('medium');
        });
      $("a.openid-link").hide();
      $("a.user-link").show();
      return false;
    });
  $("a.user-link:not(.openid-processed)", context)
    .addClass('openid-processed')
    .click(function() {
      $("#edit-openid-url-wrapper").hide();
      $("#edit-pass-wrapper").show();
      $("#edit-name-wrapper").show();
      $("a.user-link").hide();
      $("a.openid-link").show();
      return false;
    });
};

