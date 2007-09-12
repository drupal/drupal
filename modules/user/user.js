// $Id: user.js,v 1.6 2007/09/12 18:29:32 goba Exp $

/**
 * Attach handlers to evaluate the strength of any password fields and to check
 * that its confirmation is correct.
 */
Drupal.behaviors.password = function(context) {
  var translate = Drupal.settings.password;
  $("input.password-field:not(.password-processed)", context).each(function() {
    var passwordInput = $(this).addClass('password-processed');
    var parent = $(this).parent();
    // Wait this number of milliseconds before checking password.
    var monitorDelay = 700;

    // Add the password strength layers.
    $(this).after('<span class="password-strength"><span class="password-title">'+ translate.strengthTitle +'</span> <span class="password-result"></span></span>').parent();
    var passwordStrength = $("span.password-strength", parent);
    var passwordResult = $("span.password-result", passwordStrength);
    parent.addClass("password-parent");

    // Add the password confirmation layer.
    var outerItem  = $(this).parent().parent();
    $("input.password-confirm", outerItem).after('<span class="password-confirm">'+ translate["confirmTitle"] +' <span></span></span>').parent().addClass("confirm-parent");
    var confirmInput = $("input.password-confirm", outerItem);
    var confirmResult = $("span.password-confirm", outerItem);
    var confirmChild = $("span", confirmResult);

    // Add the description box at the end.
    $(confirmInput).parent().after('<div class="password-description"></div>');
    var passwordDescription = $("div.password-description", $(this).parent().parent()).hide();

    // Check the password fields.
    var passwordCheck = function () {
      // Remove timers for a delayed check if they exist.
      if (this.timer) {
        clearTimeout(this.timer);
      }

      // Verify that there is a password to check.
      if (!passwordInput.val()) {
        passwordStrength.css({ visibility: "hidden" });
        passwordDescription.hide();
        return;
      }

      // Evaluate password strength.

      var result = Drupal.evaluatePasswordStrength(passwordInput.val());
      passwordResult.html(result.strength == "" ? "" : translate[result.strength +"Strength"]);

      // Map the password strength to the relevant drupal CSS class.
      var classMap = { low: "error", medium: "warning", high: "ok" };
      var newClass = classMap[result.strength] || "";

      // Remove the previous styling if any exists; add the new class.
      if (this.passwordClass) {
        passwordResult.removeClass(this.passwordClass);
        passwordDescription.removeClass(this.passwordClass);
      }
      passwordDescription.html(result.message);
      passwordResult.addClass(newClass);
      if (result.strength == "high") {
        passwordDescription.hide();
      }
      else {
        passwordDescription.addClass(newClass);
      }
      this.passwordClass = newClass;

      // Check that password and confirmation match.

      // Hide the result layer if confirmation is empty, otherwise show the layer.
      confirmResult.css({ visibility: (confirmInput.val() == "" ? "hidden" : "visible") });

      var success = passwordInput.val() == confirmInput.val();

      // Remove the previous styling if any exists.
      if (this.confirmClass) {
        confirmChild.removeClass(this.confirmClass);
      }

      // Fill in the correct message and set the class accordingly.
      var confirmClass = success ? "ok" : "error";
      confirmChild.html(translate["confirm"+ (success ? "Success" : "Failure")]).addClass(confirmClass);
      this.confirmClass = confirmClass;

      // Show the indicator and tips.
      passwordStrength.css({ visibility: "visible" });
      passwordDescription.show();
    };

    // Do a delayed check on the password fields.
    var passwordDelayedCheck = function() {
      // Postpone the check since the user is most likely still typing.
      if (this.timer) {
        clearTimeout(this.timer);
      }

      // When the user clears the field, hide the tips immediately.
      if (!passwordInput.val()) {
        passwordStrength.css({ visibility: "hidden" });
        passwordDescription.hide();
        return;
      }

      // Schedule the actual check.
      this.timer = setTimeout(passwordCheck, monitorDelay);
    };
    // Monitor keyup and blur events.
    // Blur must be used because a mouse paste does not trigger keyup.
    passwordInput.keyup(passwordDelayedCheck).blur(passwordCheck);
    confirmInput.keyup(passwordDelayedCheck).blur(passwordCheck);
  });
};

/**
 * Evaluate the strength of a user's password.
 *
 * Returns the estimated strength and the relevant output message.
 */
Drupal.evaluatePasswordStrength = function(value) {
  var strength = "", msg = "", translate = Drupal.settings.password;

  var hasLetters = value.match(/[a-zA-Z]+/);
  var hasNumbers = value.match(/[0-9]+/);
  var hasPunctuation = value.match(/[^a-zA-Z0-9]+/);
  var hasCasing = value.match(/[a-z]+.*[A-Z]+|[A-Z]+.*[a-z]+/);

  // Check if the password is blank.
  if (!value.length) {
    strength = "";
    msg = "";
  }
  // Check if length is less than 6 characters.
  else if (value.length < 6) {
    strength = "low";
    msg = translate.tooShort;
  }
  // Check if password is the same as the username (convert both to lowercase).
  else if (value.toLowerCase() == translate.username.toLowerCase()) {
    strength  = "low";
    msg = translate.sameAsUsername;
  }
  // Check if it contains letters, numbers, punctuation, and upper/lower case.
  else if (hasLetters && hasNumbers && hasPunctuation && hasCasing) {
    strength = "high";
  }
  // Password is not secure enough so construct the medium-strength message.
  else {
    // Extremely bad passwords still count as low.
    var count = (hasLetters ? 1 : 0) + (hasNumbers ? 1 : 0) + (hasPunctuation ? 1 : 0) + (hasCasing ? 1 : 0);
    strength = count > 1 ? "medium" : "low";

    msg = [];
    if (!hasLetters || !hasCasing) {
      msg.push(translate.addLetters);
    }
    if (!hasNumbers) {
      msg.push(translate.addNumbers);
    }
    if (!hasPunctuation) {
      msg.push(translate.addPunctuation);
    }
    msg = translate.needsMoreVariation +"<ul><li>"+ msg.join("</li><li>") +"</li></ul>";
  }

  return { strength: strength, message: msg };
};

/**
 * Set the client's system timezone as default values of form fields.
 */
Drupal.setDefaultTimezone = function() {
  var offset = new Date().getTimezoneOffset() * -60;
  $("#edit-date-default-timezone, #edit-user-register-timezone").val(offset);
};

/**
 * On the admin/user/settings page, conditionally show all of the
 * picture-related form elements depending on the current value of the
 * "Picture support" radio buttons.
 */
Drupal.behaviors.userSettings = function (context) {
  $('div.user-admin-picture-radios input[type=radio]:not(.userSettings-processed)', context).addClass('userSettings-processed').click(function () {
    $('div.user-admin-picture-settings', context)[['hide', 'show'][this.value]]();
  });
};

