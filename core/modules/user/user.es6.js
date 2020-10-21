/**
 * @file
 * User behaviors.
 */

(($, Drupal) => {
  /**
   * An object containing CSS classes used for password widget.
   *
   * @type {object}
   * @prop {string} passwordParent - A CSS class for the parent element.
   * @prop {string} passwordsMatch - A CSS class indicating password match.
   * @prop {string} passwordsNotMatch - A CSS class indicating passwords
   *   doesn't match.
   * @prop {string} passwordWeak - A CSS class indicating weak password
   *   strength.
   * @prop {string} passwordFair - A CSS class indicating fair password
   *   strength.
   * @prop {string} passwordGood - A CSS class indicating good password
   *   strength.
   * @prop {string} passwordStrong - A CSS class indicating strong password
   *   strength.
   * @prop {string} widgetInitial - Initial CSS class that should be removed
   *   on a state change.
   * @prop {string} passwordEmpty - A CSS class indicating password has not
   *   been filled.
   * @prop {string} passwordFilled - A CSS class indicating password has
   *   been filled.
   * @prop {string} confirmEmpty - A CSS class indicating password
   *   confirmation has not been filled.
   * @prop {string} confirmFilled - A CSS class indicating password
   *   confirmation has been filled.
   */
  Drupal.user = {
    password: {
      css: {
        passwordParent: 'password-parent',
        passwordsMatch: 'ok',
        passwordsNotMatch: 'error',
        passwordWeak: 'is-weak',
        passwordFair: 'is-fair',
        passwordGood: 'is-good',
        passwordStrong: 'is-strong',
        widgetInitial: '',
        passwordEmpty: '',
        passwordFilled: '',
        confirmEmpty: '',
        confirmFilled: '',
      },
    },
  };

  /**
   * Attach handlers to evaluate the strength of any password fields and to
   * check that its confirmation is correct.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches password strength indicator and other relevant validation to
   *   password fields.
   */
  Drupal.behaviors.password = {
    attach(context, settings) {
      const cssClasses = Drupal.user.password.css;
      $(context)
        .find('input.js-password-field')
        .once('password')
        .each((index, value) => {
          const $mainInput = $(value);
          const $mainInputParent = $mainInput
            .parent()
            .addClass(cssClasses.passwordParent);
          const $passwordWidget = $mainInput.closest(
            '.js-form-type-password-confirm',
          );
          const $confirmInput = $passwordWidget.find(
            'input.js-password-confirm',
          );
          const $passwordConfirmMessage = $(
            Drupal.theme('passwordConfirmMessage', settings.password),
          );

          let $passwordMatchStatus = $passwordConfirmMessage
            .find('[data-drupal-selector="password-match-status-text"]')
            .first();
          if ($passwordMatchStatus.length === 0) {
            $passwordMatchStatus = $passwordConfirmMessage.find('span').first();
            Drupal.deprecationError({
              message:
                'Returning <span> without data-drupal-selector="password-match-status-text" attribute is deprecated in drupal:9.1.0 and is removed from drupal:10.0.0. See https://www.drupal.org/node/3152101',
            });
          }

          const $confirmInputParent = $confirmInput
            .parent()
            .addClass('confirm-parent')
            .append($passwordConfirmMessage);

          // List of classes to be removed from the strength bar on a state
          // change.
          const passwordStrengthBarClassesToRemove = [
            cssClasses.passwordWeak || '',
            cssClasses.passwordFair || '',
            cssClasses.passwordGood || '',
            cssClasses.passwordStrong || '',
          ]
            .join(' ')
            .trim();

          // List of classes to be removed from the text wrapper on a state
          // change.
          const confirmTextWrapperClassesToRemove = [
            cssClasses.passwordsMatch || '',
            cssClasses.passwordsNotMatch || '',
          ]
            .join(' ')
            .trim();

          // List of classes to be removed from the widget on a state change.
          const widgetClassesToRemove = [
            cssClasses.widgetInitial || '',
            cssClasses.passwordEmpty || '',
            cssClasses.passwordFilled || '',
            cssClasses.confirmEmpty || '',
            cssClasses.confirmFilled || '',
          ]
            .join(' ')
            .trim();

          const password = {};

          // If the password strength indicator is enabled, add its markup.
          if (settings.password.showStrengthIndicator) {
            const $passwordStrength = $(
              Drupal.theme('passwordStrength', settings.password),
            );
            password.$strengthBar = $passwordStrength
              .find('[data-drupal-selector="password-strength-indicator"]')
              .first();
            if (password.$strengthBar.length === 0) {
              password.$strengthBar = $passwordStrength
                .find('.js-password-strength__indicator')
                .first();
              Drupal.deprecationError({
                message:
                  'The js-password-strength__indicator class is deprecated in drupal:9.1.0 and is removed from drupal:10.0.0. Replace js-password-strength__indicator with a data-drupal-selector="password-strength-indicator" attribute. See https://www.drupal.org/node/3152101',
              });
            }
            password.$strengthTextWrapper = $passwordStrength
              .find('[data-drupal-selector="password-strength-text"]')
              .first();
            if (password.$strengthTextWrapper.length === 0) {
              password.$strengthTextWrapper = $passwordStrength
                .find('.js-password-strength__text')
                .first();
              Drupal.deprecationError({
                message:
                  'The js-password-strength__text class is deprecated in drupal:9.1.0 and is removed from drupal:10.0.0. Replace js-password-strength__text with a data-drupal-selector="password-strength-text" attribute. See https://www.drupal.org/node/3152101',
              });
            }
            password.$suggestions = $(
              Drupal.theme('passwordSuggestions', settings.password, []),
            );

            password.$suggestions.hide();
            $mainInputParent.append($passwordStrength);
            $confirmInputParent.after(password.$suggestions);
          }

          /**
           * Adds classes to the widget indicating if the elements are filled.
           */
          const addWidgetClasses = () => {
            $passwordWidget
              .addClass(
                $mainInput.val()
                  ? cssClasses.passwordFilled
                  : cssClasses.passwordEmpty,
              )
              .addClass(
                $confirmInput.val()
                  ? cssClasses.confirmFilled
                  : cssClasses.confirmEmpty,
              );
          };

          /**
           * Check that password and confirmation inputs match.
           *
           * @param {string} confirmInputVal
           *   The value of the confirm input.
           */
          const passwordCheckMatch = (confirmInputVal) => {
            const passwordsAreMatching = $mainInput.val() === confirmInputVal;
            const confirmClass = passwordsAreMatching
              ? cssClasses.passwordsMatch
              : cssClasses.passwordsNotMatch;
            const confirmMessage = passwordsAreMatching
              ? settings.password.confirmSuccess
              : settings.password.confirmFailure;

            // Update the success message and set the class if needed.
            if (
              !$passwordMatchStatus.hasClass(confirmClass) ||
              !$passwordMatchStatus.html() === confirmMessage
            ) {
              if (confirmTextWrapperClassesToRemove) {
                $passwordMatchStatus.removeClass(
                  confirmTextWrapperClassesToRemove,
                );
              }
              $passwordMatchStatus.html(confirmMessage).addClass(confirmClass);
            }
          };

          /**
           * Checks the password strength.
           */
          const passwordCheck = () => {
            if (settings.password.showStrengthIndicator) {
              // Evaluate the password strength.
              const result = Drupal.evaluatePasswordStrength(
                $mainInput.val(),
                settings.password,
              );
              const $currentPasswordSuggestions = $(
                Drupal.theme(
                  'passwordSuggestions',
                  settings.password,
                  result.messageTips,
                ),
              );

              // Update the suggestions for how to improve the password if needed.
              if (
                password.$suggestions.html() !==
                $currentPasswordSuggestions.html()
              ) {
                password.$suggestions.replaceWith($currentPasswordSuggestions);
                password.$suggestions = $currentPasswordSuggestions.toggle(
                  // Only show the description box if a weakness exists in the
                  // password.
                  result.strength !== 100,
                );
              }

              if (passwordStrengthBarClassesToRemove) {
                password.$strengthBar.removeClass(
                  passwordStrengthBarClassesToRemove,
                );
              }
              // Adjust the length of the strength indicator.
              password.$strengthBar
                .css('width', `${result.strength}%`)
                .addClass(result.indicatorClass);

              // Update the strength indication text.
              password.$strengthTextWrapper.html(result.indicatorText);
            }

            // Check the value in the confirm input and show results.
            if ($confirmInput.val()) {
              passwordCheckMatch($confirmInput.val());
              $passwordConfirmMessage.css({ visibility: 'visible' });
            } else {
              $passwordConfirmMessage.css({ visibility: 'hidden' });
            }

            if (widgetClassesToRemove) {
              $passwordWidget.removeClass(widgetClassesToRemove);
              addWidgetClasses();
            }
          };

          if (widgetClassesToRemove) {
            addWidgetClasses();
          }

          // Monitor input events.
          $mainInput.on('input', passwordCheck);
          $confirmInput.on('input', passwordCheck);
        });
    },
  };

  /**
   * Evaluate the strength of a user's password.
   *
   * Returns the estimated strength and the relevant output message.
   *
   * @param {string} password
   *   The password to evaluate.
   * @param {object} passwordSettings
   *   A password settings object containing the text to display and the CSS
   *   classes for each strength level.
   *
   * @return {object}
   *   An object containing strength, message, indicatorText and indicatorClass.
   */
  Drupal.evaluatePasswordStrength = (password, passwordSettings) => {
    password = password.trim();
    let indicatorText;
    let indicatorClass;
    let weaknesses = 0;
    let strength = 100;
    let msg = [];

    const hasLowercase = /[a-z]/.test(password);
    const hasUppercase = /[A-Z]/.test(password);
    const hasNumbers = /[0-9]/.test(password);
    const hasPunctuation = /[^a-zA-Z0-9]/.test(password);

    // If there is a username edit box on the page, compare password to that,
    // otherwise use value from the database.
    const $usernameBox = $('input.username');
    const username =
      $usernameBox.length > 0 ? $usernameBox.val() : passwordSettings.username;

    // Lose 5 points for every character less than 12, plus a 30 point penalty.
    if (password.length < 12) {
      msg.push(passwordSettings.tooShort);
      strength -= (12 - password.length) * 5 + 30;
    }

    // Count weaknesses.
    if (!hasLowercase) {
      msg.push(passwordSettings.addLowerCase);
      weaknesses += 1;
    }
    if (!hasUppercase) {
      msg.push(passwordSettings.addUpperCase);
      weaknesses += 1;
    }
    if (!hasNumbers) {
      msg.push(passwordSettings.addNumbers);
      weaknesses += 1;
    }
    if (!hasPunctuation) {
      msg.push(passwordSettings.addPunctuation);
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
    }

    // Check if password is the same as the username.
    if (password !== '' && password.toLowerCase() === username.toLowerCase()) {
      msg.push(passwordSettings.sameAsUsername);
      // Passwords the same as username are always very weak.
      strength = 5;
    }

    const cssClasses = Drupal.user.password.css;

    // Based on the strength, work out what text should be shown by the
    // password strength meter.
    if (strength < 60) {
      indicatorText = passwordSettings.weak;
      indicatorClass = cssClasses.passwordWeak;
    } else if (strength < 70) {
      indicatorText = passwordSettings.fair;
      indicatorClass = cssClasses.passwordFair;
    } else if (strength < 80) {
      indicatorText = passwordSettings.good;
      indicatorClass = cssClasses.passwordGood;
    } else if (strength <= 100) {
      indicatorText = passwordSettings.strong;
      indicatorClass = cssClasses.passwordStrong;
    }

    // Assemble the final message while keeping the original message array.
    const messageTips = msg;
    msg = `${passwordSettings.hasWeaknesses}<ul><li>${msg.join(
      '</li><li>',
    )}</li></ul>`;

    return Drupal.deprecatedProperty({
      target: {
        strength,
        message: msg,
        indicatorText,
        indicatorClass,
        messageTips,
      },
      deprecatedProperty: 'message',
      message:
        'The message property is deprecated in drupal:9.1.0 and is removed from drupal:10.0.0. The markup should be constructed using messageTips property and Drupal.theme.passwordSuggestions. See https://www.drupal.org/node/3130352',
    });
  };
})(jQuery, Drupal);
