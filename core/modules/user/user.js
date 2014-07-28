(function ($) {

  "use strict";

  /**
   * Attach handlers to evaluate the strength of any password fields and to check
   * that its confirmation is correct.
   */
  Drupal.behaviors.password = {
    attach: function (context, settings) {
      var translate = settings.password;
      $(context).find('input.password-field').once('password', function () {
        var passwordInput = $(this);
        var innerWrapper = $(this).parent();
        var outerWrapper = $(this).parent().parent();

        // Add identifying class to password element parent.
        innerWrapper.addClass('password-parent');

        // Add the password confirmation layer.
        outerWrapper.find('input.password-confirm').parent().append('<div class="password-confirm">' + translate.confirmTitle + ' <span></span></div>').addClass('confirm-parent');
        var confirmInput = outerWrapper.find('input.password-confirm');
        var confirmResult = outerWrapper.find('div.password-confirm');
        var confirmChild = confirmResult.find('span');

        // If the password strength indicator is enabled, add its markup.
        if (settings.password.showStrengthIndicator) {
          var passwordMeter = '<div class="password-strength"><div class="password-strength__meter"><div class="password-strength__indicator"></div></div><div class="password-strength__title">' + translate.strengthTitle + ' </div><div class="password-strength__text" aria-live="assertive"></div></div>';
          confirmInput.parent().after('<div class="password-suggestions description"></div>');
          innerWrapper.append(passwordMeter);
          var passwordDescription = outerWrapper.find('div.password-suggestions').hide();
        }

        // Check that password and confirmation inputs match.
        var passwordCheckMatch = function (confirmInputVal) {
          var success = passwordInput.val() === confirmInputVal;
          var confirmClass = success ? 'ok' : 'error';

          // Fill in the success message and set the class accordingly.
          confirmChild.html(translate['confirm' + (success ? 'Success' : 'Failure')])
            .removeClass('ok error').addClass(confirmClass);
        };

        // Check the password strength.
        var passwordCheck = function () {
          if (settings.password.showStrengthIndicator) {
            // Evaluate the password strength.
            var result = Drupal.evaluatePasswordStrength(passwordInput.val(), settings.password);

            // Update the suggestions for how to improve the password.
            if (passwordDescription.html() !== result.message) {
              passwordDescription.html(result.message);
            }

            // Only show the description box if a weakness exists in the password.
            passwordDescription.toggle(result.strength !== 100);

            // Adjust the length of the strength indicator.
            innerWrapper.find('.password-strength__indicator')
              .css('width', result.strength + '%')
              .css('background-color', result.indicatorColor);

            // Update the strength indication text.
            innerWrapper.find('.password-strength__text').html(result.indicatorText);
          }

          // Check the value in the confirm input and show results.
          if (confirmInput.val()) {
            passwordCheckMatch(confirmInput.val());
            confirmResult.css({ visibility: 'visible' });
          }
          else {
            confirmResult.css({ visibility: 'hidden' });
          }
        };

        // Monitor input events.
        passwordInput.on('input', passwordCheck);
        confirmInput.on('input', passwordCheck);
      });
    }
  };

  /**
   * Evaluate the strength of a user's password.
   *
   * Returns the estimated strength and the relevant output message.
   */
  Drupal.evaluatePasswordStrength = function (password, translate) {
    var indicatorText, indicatorColor, weaknesses = 0, strength = 100, msg = [];

    var hasLowercase = /[a-z]+/.test(password);
    var hasUppercase = /[A-Z]+/.test(password);
    var hasNumbers = /[0-9]+/.test(password);
    var hasPunctuation = /[^a-zA-Z0-9]+/.test(password);

    // If there is a username edit box on the page, compare password to that, otherwise
    // use value from the database.
    var usernameBox = $('input.username');
    var username = (usernameBox.length > 0) ? usernameBox.val() : translate.username;

    // Lose 5 points for every character less than 6, plus a 30 point penalty.
    if (password.length < 6) {
      msg.push(translate.tooShort);
      strength -= ((6 - password.length) * 5) + 30;
    }

    // Count weaknesses.
    if (!hasLowercase) {
      msg.push(translate.addLowerCase);
      weaknesses++;
    }
    if (!hasUppercase) {
      msg.push(translate.addUpperCase);
      weaknesses++;
    }
    if (!hasNumbers) {
      msg.push(translate.addNumbers);
      weaknesses++;
    }
    if (!hasPunctuation) {
      msg.push(translate.addPunctuation);
      weaknesses++;
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
      msg.push(translate.sameAsUsername);
      // Passwords the same as username are always very weak.
      strength = 5;
    }

    // Based on the strength, work out what text should be shown by the password strength meter.
    if (strength < 60) {
      indicatorText = translate.weak;
      indicatorColor = '#bb5555';
    }
    else if (strength < 70) {
      indicatorText = translate.fair;
      indicatorColor = '#bbbb55';
    }
    else if (strength < 80) {
      indicatorText = translate.good;
      indicatorColor = '#4863a0';
    }
    else if (strength <= 100) {
      indicatorText = translate.strong;
      indicatorColor = '#47c965';
    }

    // Assemble the final message.
    msg = translate.hasWeaknesses + '<ul><li>' + msg.join('</li><li>') + '</li></ul>';
    return { strength: strength, message: msg, indicatorText: indicatorText, indicatorColor: indicatorColor };

  };

  /**
   * Field instance settings screen: force the 'Display on registration form'
   * checkbox checked whenever 'Required' is checked.
   */
  Drupal.behaviors.fieldUserRegistration = {
    attach: function (context, settings) {
      var $checkbox = $('form#field-ui-field-edit-form input#edit-instance-settings-user-register-form');

      if ($checkbox.length) {
        $(context).find('input#edit-instance-required').once('user-register-form-checkbox', function () {
          $(this).on('change', function (e) {
            if ($(this).prop('checked')) {
              $checkbox.prop('checked', true);
            }
          });
        });

      }
    }
  };

})(jQuery);
