/**
 * @file
 * Theme elements for user password forms.
 *
 * This file is a temporary addition to the theme to override
 * stable/drupal.user. This is to help surface potential issues that may arise
 * once the theme no longer depends on stable/drupal.user.
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
