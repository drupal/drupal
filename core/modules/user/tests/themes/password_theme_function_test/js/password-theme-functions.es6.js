/**
 * @file
 * Overrides password theme functions for testing.
 */

((Drupal) => {
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
      '<span>Overridden passwordStrength:</span><div class="password-strength__indicator js-password-strength__indicator the-prior-class-is-deprecated" data-drupal-selector="a-distinct-absence-of-password-strength-indicator"></div>';
    const strengthText =
      '<span class="password-strength__text js-password-strength__text the-prior-class-is-deprecated" data-drupal-selector="a-distinct-absence-of-password-strength-text"></span>';
    return `
      <div class="password-strength">
        <div class="password-strength__meter" data-drupal-selector="password-strength-meter">${strengthIndicator}</div>
        <div aria-live="polite" aria-atomic="true" class="password-strength__title">${strengthTitle} ${strengthText}</div>
      </div>
    `;
  };

  /**
   * Constructs password suggestions tips.
   *
   * @param {object} passwordSettings
   *   An object containing password related settings and translated text to
   *   display.
   * @param {string} passwordSettings.hasWeaknesses
   *   The title that precedes tips.
   * @param {Array.<string>} tips
   *   Array containing the tips.
   *
   * @return {string}
   *   Markup for password suggestions.
   */
  Drupal.theme.passwordSuggestions = ({ hasWeaknesses }, tips) =>
    `<div class="password-suggestions">Overridden passwordSuggestions: ${
      tips.length
        ? `${hasWeaknesses}<ul><li>${tips.join('</li><li>')}</li></ul>`
        : ''
    }</div>`;

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
      '<span>Overridden passwordConfirmMessage:</span><span data-drupal-selector="a-distinct-absence-of-password-match-status-text"></span>';
    return `<div aria-live="polite" aria-atomic="true" class="password-confirm-message" data-drupal-selector="password-confirm-message">${confirmTitle} ${confirmTextWrapper}</div>`;
  };

  /**
   * Confirm deprecation of property in evaluatePasswordStrength() return.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches a check for the deprecated `message` property in the object
   *   returned by Drupal.evaluatePasswordStrength().
   */
  Drupal.behaviors.passwordThemeFunctionTest = {
    attach(context, settings) {
      const strength = Drupal.evaluatePasswordStrength(
        'password',
        settings.password,
      );

      // eslint-disable-next-line no-unused-vars
      const { message } = strength;
    },
  };
})(Drupal);
