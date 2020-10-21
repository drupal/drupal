/**
 * Checks if a user is logged in.
 *
 * @param {function} callback
 *   A callback which will be called, when the login status has been checked.
 * @return {object}
 *   The drupalUserIsLoggedIn command.
 */
exports.command = function drupalUserIsLoggedIn(callback) {
  if (typeof callback === 'function') {
    this.getCookies((cookies) => {
      const sessionExists = cookies.value.some((cookie) =>
        cookie.name.match(/^S?SESS/),
      );

      callback.call(this, sessionExists);
    });
  }

  return this;
};
