import { execSync } from 'child_process';
import { URL } from 'url';
import { commandAsWebserver } from '../globals';

/**
 * Logs in as the admin user.
 *
 * @param {function} callback
 *   A callback which will allow running commands as an administrator.
 * @return {object}
 *   The drupalLoginAsAdmin command.
 */
exports.command = function drupalLoginAsAdmin(callback) {
  const self = this;
  this.drupalUserIsLoggedIn(sessionExists => {
    if (sessionExists) {
      this.drupalLogout();
    }
    const userLink = execSync(
      commandAsWebserver(
        `php ./scripts/test-site.php user-login 1 --site-path ${this.globals.drupalSitePath}`,
      ),
    );

    this.drupalRelativeURL(userLink.toString());

    this.drupalUserIsLoggedIn(sessionExists => {
      if (!sessionExists) {
        throw new Error('Logging in as an admin user failed.');
      }
    });
  });

  if (typeof callback === 'function') {
    callback.call(self);
  }

  this.drupalLogout({ silent: true });

  return this;
};
