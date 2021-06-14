/**
 * Logs into Drupal as the given user.
 *
 * @param {string} name
 *   The user name.
 * @param {string} password
 *   The user password.
 * @return {object}
 *   The drupalUserIsLoggedIn command.
 */
exports.command = function drupalLogin({ name, password }) {
  this.drupalUserIsLoggedIn((sessionExists) => {
    // Log the current user out if necessary.
    if (sessionExists) {
      this.drupalLogout();
    }
    // Log in with the given credentials.
    this.drupalRelativeURL('/user/login')
      .setValue('input[name="name"]', name)
      .setValue('input[name="pass"]', password)
      .submitForm('#user-login-form');
    // Assert that a user is logged in.
    this.drupalUserIsLoggedIn((sessionExists) => {
      this.assert.equal(
        sessionExists,
        true,
        `The user "${name}" was logged in.`,
      );
    });
  });

  return this;
};
