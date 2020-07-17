/**
 * @file
 * Theme elements for user password forms.
 */

(Drupal => {
  /**
   * Constructs a password confirm message element
   *
   * @return {string}
   *   A string representing a DOM fragment.
   */
  Drupal.theme.passwordConfirmMessage = translate =>
    `<div aria-live="polite" aria-atomic="true" class="password-confirm-message js-password-confirm-message">${translate.confirmTitle} <span></span></div>`;
})(Drupal);
