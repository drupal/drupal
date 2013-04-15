(function ($) {

"use strict";

Drupal.behaviors.openid = {
  attach: function (context) {
    function clearStatus ($form) {
      $form.find('input:first').focus();
      // Clear input fields and reset any validation errors.
      $form[0].reset();

      // Reset error state.
      $form.find('.error').removeClass('error');

      // Forget saved identifier.
      $.cookie('Drupal.visitor.openid_identifier', null);
    }

    if ($('#block-user-login').length) {
      var $login_form = $('#user-login-form');
      var $openid_form = $('#openid-login-form');

      // Change link text and triggers loginchange event.
      var toggleClick = true;
      $('#block-user-login .openid-link').on('click', function() {
        if (toggleClick) {
          $(this).html(Drupal.t('Cancel OpenID login'));
          $login_form.hide();
          $openid_form.show();
          clearStatus($login_form);
          // Move focus to OpenID input.
          $('#edit-openid-identifier').focus();
        }
        else {
          $(this).html(Drupal.t('Log in using OpenID'));
          $login_form.show();
          $openid_form.hide();
          clearStatus($openid_form);
        }
        toggleClick = !toggleClick;
      });
    }

  }
};
})(jQuery, Drupal);
