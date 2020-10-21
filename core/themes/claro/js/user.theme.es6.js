/**
 * @file
 * Password confirm widget template overrides.
 */

((Drupal) => {
  Object.assign(Drupal.user.password.css, {
    passwordWeak: 'is-weak',
    widgetInitial: 'is-initial',
    passwordEmpty: 'is-password-empty',
    passwordFilled: 'is-password-filled',
    confirmEmpty: 'is-confirm-empty',
    confirmFilled: 'is-confirm-filled',
  });

  /**
   * Constructs a password confirm message element.
   *
   * @param {object} passwordSettings
   *   An object containing password related settings and translated text to
   *   display.
   * @param {string} passwordSettings.confirmTitle
   *   The translated confirm description that labels the actual confirm text.
   *
   * @return {string}
   *   Markup for the password confirm message.
   */
  Drupal.theme.passwordConfirmMessage = ({ confirmTitle }) => {
    const confirmTextWrapper =
      '<span class="password-match-message__text" data-drupal-selector="password-match-status-text"></span>';
    return `<div aria-live="polite" aria-atomic="true" class="password-match-message" data-drupal-selector="password-confirm-message">${confirmTitle} ${confirmTextWrapper}</div>`;
  };

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
   *   Markup for the password strength indicator.
   */
  Drupal.theme.passwordStrength = ({ strengthTitle }) => {
    const strengthBar =
      '<div class="password-strength__bar" data-drupal-selector="password-strength-indicator"></div>';
    const strengthText =
      '<span class="password-strength__text" data-drupal-selector="password-strength-text"></span>';
    return `
      <div class="password-strength">
        <div class="password-strength__track" data-drupal-selector="password-strength-meter">${strengthBar}</div>
        <div aria-live="polite" aria-atomic="true" class="password-strength__title">${strengthTitle} ${strengthText}</div>
      </div>
    `;
  };

  /**
   * Constructs password suggestions tips.
   *
   * @param {object} passwordSettings
   *   An object containing password related settings and translated tex  t to
   *   display.
   * @param {string} passwordSettings.hasWeaknesses
   *   The title that precedes tips.
   * @param {Array.<string>} tips
   *   Array containing the tips.
   *
   * @return {string}
   *   Markup for the password suggestions.
   */
  Drupal.theme.passwordSuggestions = ({ hasWeaknesses }, tips) =>
    `<div class="password-suggestions">${
      tips.length
        ? `${hasWeaknesses}<ul class="password-suggestions__tips"><li class="password-suggestions__tip">${tips.join(
            '</li><li class="password-suggestions__tip">',
          )}</li></ul>`
        : ''
    }</div>`;
})(Drupal);
