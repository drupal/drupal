/**
 * @file
 * Stable theme overrides for user password forms.
 */

((Drupal) => {
  /**
   * Constructs a password confirm message element
   *
   * @return {string}
   *   A string representing a DOM fragment.
   */
  Drupal.theme.passwordConfirmMessage = (translate) =>
    `<div aria-live="polite" aria-atomic="true" class="password-confirm js-password-confirm js-password-confirm-message" data-drupal-selector="password-confirm-message">${translate.confirmTitle} <span data-drupal-selector="password-match-status-text"></span></div>`;

  /**
   * Constructs a password strength message.
   *
   * @param {object} passwordSettings
   *   An object containing password related settings and translated text to
   *   display.
   * @param {string} passwordSettings.strengthTitle
   *   The title that precedes the strength text.
   *
   * @return {string}
   *   Markup for password strength message.
   */
  Drupal.theme.passwordStrength = ({ strengthTitle }) => {
    const strengthIndicator =
      '<div class="password-strength__indicator js-password-strength__indicator" data-drupal-selector="password-strength-indicator"></div>';
    const strengthText =
      '<span class="password-strength__text js-password-strength__text" data-drupal-selector="password-strength-text"></span>';
    return `
      <div class="password-strength">
        <div class="password-strength__meter" data-drupal-selector="password-strength-meter">${strengthIndicator}</div>
        <div aria-live="polite" aria-atomic="true" class="password-strength__title">${strengthTitle} ${strengthText}</div>
      </div>
    `;
  };
})(Drupal);
