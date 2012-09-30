(function ($) {

"use strict";

Drupal.behaviors.openid = {
  attach: function (context) {
    var $login = $('#user-login-form');
    var $openid = $('#openid-login-form');

    var cookie = $.cookie('Drupal.visitor.openid_identifier');
    if (cookie || location.hash === '#openid-login') {
      $openid.show()
        .find('[name="openid_identifier"]').once('openid')
        .val(cookie);
      $login.hide();
    }

    // Switch between the default login form and the OpenID login form.
    $('#block-user-login').once('openid').on('click', '.openid-link, .user-link', function (e) {
      $openid.toggle();
      $login.toggle();

      var $showForm = $(this).hasClass('openid-link') ? $openid : $login;
      $showForm.find('input:first').focus();
      // Clear input fields and reset any validation errors.
      $showForm[0].reset();

      // Reset error state.
      $('#messages').find('div.error').hide();
      $('#block-user-login').find('input').removeClass('error');

      // Forget saved identifier.
      $.cookie('Drupal.visitor.openid_identifier', null);
    });
  }
};

})(jQuery);
