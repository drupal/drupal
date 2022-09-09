const { execSync } = require('child_process');
const { URL } = require('url');

/**
 * Logs out from a Drupal site.
 *
 * @param {object} [settings={}]
 *   The settings object.
 * @param {boolean} [settings.silent=false]
 *   If the command should be run silently.
 * @param {function} callback
 *   A callback which will be called, when the logout is finished.
 * @return {object}
 *   The drupalLogout command.
 */
exports.command = function drupalLogout({ silent = false } = {}, callback) {
  const self = this;

  this.drupalRelativeURL('/user/logout');

  this.drupalUserIsLoggedIn((sessionExists) => {
    if (silent) {
      if (sessionExists) {
        throw new Error('Logging out failed.');
      }
    } else {
      this.assert.equal(sessionExists, false, 'The user was logged out.');
    }
  });

  if (typeof callback === 'function') {
    callback.call(self);
  }

  return this;
};
