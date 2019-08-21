/**
 * @file
 * Stable theme overrides for user password forms.
 */

(Drupal => {
  /**
   * Constucts a password confirm message element
   *
   * @return {string}
   *   A string representing a DOM fragment.
   */
  Drupal.theme.passwordConfirmMessage = translate =>
    `<div aria-live="polite" aria-atomic="true" class="password-confirm js-password-confirm js-password-confirm-message">${
      translate.confirmTitle
    } <span></span></div>`;
})(Drupal);
