import { execSync } from 'child_process';
import { commandAsWebserver } from '../globals';

/**
 * Uninstalls a test Drupal site.
 *
 * @param {function} callback
 *   A callback which will be called, when the uninstallation is finished.
 * @return {object}
 *   The 'browser' object.
 */
exports.command = function drupalUninstall(callback) {
  const self = this;
  const prefix = this.globals.drupalDbPrefix;

  // Check for any existing errors, because running this will cause Nightwatch to hang.
  if (!this.currentTest.results.errors && !this.currentTest.results.failed) {
    const dbOption =
      process.env.DRUPAL_TEST_DB_URL.length > 0
        ? `--db-url ${process.env.DRUPAL_TEST_DB_URL}`
        : '';
    try {
      if (!prefix || !prefix.length) {
        throw new Error(
          'Missing database prefix parameter, unable to uninstall Drupal (the initial install was probably unsuccessful).',
        );
      }
      execSync(
        commandAsWebserver(
          `php ./scripts/test-site.php tear-down ${prefix} ${dbOption}`,
        ),
      );
    } catch (error) {
      this.assert.fail(error);
    }
  }

  // Nightwatch doesn't like it when no actions are added in a command file.
  // https://github.com/nightwatchjs/nightwatch/issues/1792
  this.pause(1);

  if (typeof callback === 'function') {
    callback.call(self);
  }
  return this;
};
