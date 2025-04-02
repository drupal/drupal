const { execSync } = require('node:child_process');
const { URL } = require('node:url');
const { commandAsWebserver } = require('../globals');

/**
 * Installs a Drupal test site.
 *
 * @param {object} [settings={}]
 *   Settings object
 * @param {string} [settings.setupFile='']
 *   Setup file used by TestSiteApplicationTest
 * @param {string} [settings.installProfile='']
 *   The install profile to use.
 * @param {string} [settings.langcode='']
 *   The language to install the site in.
 * @param {function} callback
 *   A callback which will be called, when the installation is finished.
 * @return {object}
 *   The 'browser' object.
 */
exports.command = function drupalInstall(
  { setupFile = '', installProfile = 'nightwatch_testing', langcode = '' } = {},
  callback,
) {
  const self = this;

  // Ensure no session cookie exists anymore; they won't work on this newly installed Drupal site anyway.
  this.cookies.deleteAll();

  try {
    setupFile = setupFile ? `--setup-file "${setupFile}"` : '';
    installProfile = `--install-profile "${installProfile}"`;
    const langcodeOption = langcode ? `--langcode "${langcode}"` : '';
    const dbOption =
      process.env.DRUPAL_TEST_DB_URL.length > 0
        ? `--db-url "${process.env.DRUPAL_TEST_DB_URL}"`
        : '';
    const install = execSync(
      commandAsWebserver(
        `php ./scripts/test-site.php install ${setupFile} ${installProfile} ${langcodeOption} --base-url ${process.env.DRUPAL_TEST_BASE_URL} ${dbOption} --json`,
      ),
    );
    const installData = JSON.parse(install.toString());
    this.globals.drupalDbPrefix = installData.db_prefix;
    this.globals.drupalSitePath = installData.site_path;
    const url = new URL(process.env.DRUPAL_TEST_BASE_URL);
    this.url(process.env.DRUPAL_TEST_BASE_URL).setCookie({
      name: 'SIMPLETEST_USER_AGENT',
      // Colons need to be URL encoded to be valid.
      value: encodeURIComponent(installData.user_agent),
      path: url.pathname,
    });
    // Set the HTTP_USER_AGENT environment variable to detect the test
    // environment in the command line.
    process.env.HTTP_USER_AGENT = installData.user_agent;
  } catch (error) {
    this.assert.fail(error);
  }

  // Nightwatch doesn't like it when no actions are added in a command file.
  // https://github.com/nightwatchjs/nightwatch/issues/1792
  this.pause(1);

  if (typeof callback === 'function') {
    callback.call(self);
  }

  return this;
};
