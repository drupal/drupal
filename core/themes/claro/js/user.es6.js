/**
 * @file
 * Overrides Drupal core user.js that provides password strength indicator.
 *
 * @todo remove these overrides after
 *   https://www.drupal.org/project/drupal/issues/3067523 has been resolved.
 */

(($, Drupal) => {
  /**
   * This overrides the default Drupal.behaviors.password functionality.
   *
   * - Markup has been moved to theme functions so that to enable customizations
   *   needed for matching Claro's design requirements
   *   (https://www.drupal.org/project/drupal/issues/3067523).
   * - Modified classes so that same class names are not being used for different
   *   elements (https://www.drupal.org/project/drupal/issues/3061265).
   */
  Drupal.behaviors.password = {
    attach(context, settings) {
      const $passwordInput = $(context)
        .find('input.js-password-field')
        .once('password');

      if ($passwordInput.length) {
        // Settings and translated messages added by
        // user_form_process_password_confirm().
        const translate = settings.password;

        // The form element object of the password input.
        const $passwordInputParent = $passwordInput.parent();

        // The password_confirm form element object.
        const $passwordWidget = $passwordInput.closest(
          '.js-form-type-password-confirm',
        );

        // The password confirm input.
        const $passwordConfirmInput = $passwordWidget.find(
          'input.js-password-confirm',
        );

        // The strength feedback element for the password input.
        const $passwordInputHelp = $(
          Drupal.theme.passwordInputHelp(translate.strengthTitle),
        );

        // The password match feedback for the password confirm input.
        const $passwordConfirmHelp = $(
          Drupal.theme.passwordConfirmHelp(translate.confirmTitle),
        );

        const $passwordInputStrengthBar = $passwordInputHelp.find(
          '.js-password-strength-bar',
        );
        const $passwordInputStrengthMessageWrapper = $passwordInputHelp.find(
          '.js-password-strength-text',
        );
        const $passwordConfirmMatch = $passwordConfirmHelp.find(
          '.js-password-match-text',
        );
        let $passwordSuggestionsTips = $(
          Drupal.theme.passwordSuggestionsTips('', ''),
        ).hide();

        // If the password strength indicator is enabled, add its markup.
        if (settings.password.showStrengthIndicator) {
          $passwordConfirmInput
            .after($passwordConfirmHelp)
            .parent()
            .after($passwordSuggestionsTips);

          $passwordInputParent.append($passwordInputHelp);
        }

        // Check that password and confirmation inputs match.
        const passwordCheckMatch = confirmInputVal => {
          if (confirmInputVal) {
            const success = $passwordInput.val() === confirmInputVal;
            const confirmClass = success ? 'ok' : 'error';
            const confirmMatchMessage = success
              ? translate.confirmSuccess
              : translate.confirmFailure;

            // Update the success message and set the class accordingly if
            // needed.
            if (
              !$passwordConfirmMatch.hasClass(confirmClass) ||
              !$passwordConfirmMatch.html() === confirmMatchMessage
            ) {
              $passwordConfirmMatch
                .html(confirmMatchMessage)
                .removeClass('ok error')
                .addClass(confirmClass);
            }
          }
        };

        // Check the password strength.
        const passwordCheck = () => {
          if (settings.password.showStrengthIndicator) {
            // Evaluate the password strength.
            const result = Drupal.evaluatePasswordStrength(
              $passwordInput.val(),
              settings.password,
            );
            const $newSuggestions = $(
              Drupal.theme.passwordSuggestionsTips(
                translate.hasWeaknesses,
                result.tips,
              ),
            );

            // Update the suggestions for how to improve the password.
            if ($newSuggestions.html() !== $passwordSuggestionsTips.html()) {
              $passwordSuggestionsTips.replaceWith($newSuggestions);
              $passwordSuggestionsTips = $newSuggestions;

              // Only show the description box if a weakness exists in the
              // password.
              $passwordSuggestionsTips.toggle(result.strength !== 100);
            }

            // Adjust the length of the strength indicator.
            $passwordInputStrengthBar
              .css('width', `${result.strength}%`)
              .removeClass('is-weak is-fair is-good is-strong')
              .addClass(result.indicatorClass);

            // Update the strength indication text if needed.
            if (
              !$passwordInputStrengthMessageWrapper.hasClass(
                result.indicatorClass,
              ) ||
              !$passwordInputStrengthMessageWrapper.html() ===
                result.indicatorText
            ) {
              $passwordInputStrengthMessageWrapper
                .html(result.indicatorText)
                .removeClass('is-weak is-fair is-good is-strong')
                .addClass(result.indicatorClass);
            }
          }

          $passwordWidget
            .removeClass('is-initial')
            .removeClass('is-password-empty is-password-filled')
            .removeClass('is-confirm-empty is-confirm-filled');

          // Check the value of the password input and add the proper classes.
          $passwordWidget.addClass(
            $passwordInput.val() ? 'is-password-filled' : 'is-password-empty',
          );

          // Check the value in the confirm input and show results.
          passwordCheckMatch($passwordConfirmInput.val());
          $passwordWidget.addClass(
            $passwordConfirmInput.val()
              ? 'is-confirm-filled'
              : 'is-confirm-empty',
          );
        };

        // Add initial classes.
        $passwordWidget
          .addClass(
            $passwordInput.val() ? 'is-password-filled' : 'is-password-empty',
          )
          .addClass(
            $passwordConfirmInput.val()
              ? 'is-confirm-filled'
              : 'is-confirm-empty',
          );

        // Monitor input events.
        $passwordInput.on('input', passwordCheck);
        $passwordConfirmInput.on('input', passwordCheck);
      }
    },
  };

  /**
   * Override the default Drupal.evaluatePasswordStrength.
   *
   * The default implementation of this function hard codes some markup inside
   * this function. Rendering markup is now handled by
   * Drupal.behaviors.password.
   *
   * @param {string} password
   *   Password to evaluate the strength.
   *
   * @param {Array.<string>} translate
   *   Settings and translated messages added by user_form_process_password_confirm().
   *
   * @return {Array.<string>}
   *   Array containing the strength, tips, indicators text and class.
   */
  Drupal.evaluatePasswordStrength = (password, translate) => {
    password = password.trim();
    let indicatorText;
    let indicatorClass;
    let weaknesses = 0;
    let strength = 100;
    const tips = [];
    const hasLowercase = /[a-z]/.test(password);
    const hasUppercase = /[A-Z]/.test(password);
    const hasNumbers = /[0-9]/.test(password);
    const hasPunctuation = /[^a-zA-Z0-9]/.test(password);

    // If there is a username edit box on the page, compare password to that,
    // otherwise use value from the database.
    const $usernameBox = $('input.username');
    const username =
      $usernameBox.length > 0 ? $usernameBox.val() : translate.username;

    // Lose 5 points for every character less than 12, plus a 30 point penalty.
    if (password.length < 12) {
      tips.push(translate.tooShort);
      strength -= (12 - password.length) * 5 + 30;
    }

    // Count weaknesses.
    if (!hasLowercase) {
      tips.push(translate.addLowerCase);
      weaknesses += 1;
    }
    if (!hasUppercase) {
      tips.push(translate.addUpperCase);
      weaknesses += 1;
    }
    if (!hasNumbers) {
      tips.push(translate.addNumbers);
      weaknesses += 1;
    }
    if (!hasPunctuation) {
      tips.push(translate.addPunctuation);
      weaknesses += 1;
    }

    // Apply penalty for each weakness (balanced against length penalty).
    switch (weaknesses) {
      case 1:
        strength -= 12.5;
        break;

      case 2:
        strength -= 25;
        break;

      case 3:
        strength -= 40;
        break;

      case 4:
        strength -= 40;
        break;

      default:
        // Default: 0. Nothing to do.
        break;
    }

    // Check if password is the same as the username.
    if (password !== '' && password.toLowerCase() === username.toLowerCase()) {
      tips.push(translate.sameAsUsername);
      // Passwords the same as username are always very weak.
      strength = 5;
    }

    // Based on the strength, work out what text should be shown by the
    // password strength meter.
    if (strength < 60) {
      indicatorText = translate.weak;
      indicatorClass = 'is-weak';
    } else if (strength < 70) {
      indicatorText = translate.fair;
      indicatorClass = 'is-fair';
    } else if (strength < 80) {
      indicatorText = translate.good;
      indicatorClass = 'is-good';
    } else if (strength <= 100) {
      indicatorText = translate.strong;
      indicatorClass = 'is-strong';
    }

    return {
      strength,
      tips,
      indicatorText,
      indicatorClass,
    };
  };

  /**
   * Password strenght feedback for password confirm's main input.
   *
   * @param {string} message
   *   The prefix text for the strength feedback word.
   *
   * @return {string}
   *   The string representing the DOM fragment.
   */
  Drupal.theme.passwordInputHelp = message =>
    `<div class="password-strength">
      <div class="password-strength__track">
        <div class="password-strength__bar js-password-strength-bar"></div>
      </div>
      <div aria-live="polite" aria-atomic="true" class="password-strength__title">
        ${message} <span class="password-strength__text js-password-strength-text"></span>
      </div>
    </div>`;

  /**
   * Password match feedback for password confirm input.
   *
   * @param {string} message
   *   The message that precedes the yes|no text.
   *
   * @return {string}
   *   A string representing the DOM fragment.
   */
  Drupal.theme.passwordConfirmHelp = message =>
    `<div aria-live="polite" aria-atomic="true" class="password-match-message">${message} <span class="password-match-message__text js-password-match-text"></span></div>`;

  /**
   * Password suggestions tips.
   *
   * @param {string} title
   *   The title that precedes tips.
   * @param {Array.<string>} tips
   *   Array containing the tips.
   *
   * @return {string}
   *   A string representing the DOM fragment.
   */
  Drupal.theme.passwordSuggestionsTips = (title, tips) =>
    `<div class="password-suggestions">${
      tips.length
        ? `${title}<ul class="password-suggestions__tips"><li class="password-suggestions__tip">${tips.join(
            '</li><li class="password-suggestions__tip">',
          )}</li></ul>`
        : ''
    }</div>`;
})(jQuery, Drupal);
