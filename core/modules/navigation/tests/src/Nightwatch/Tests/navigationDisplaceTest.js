/**
 * Verify that Drupal.displace() attribute is properly added by JavaScript.
 */
module.exports = {
  '@tags': ['core', 'navigation'],
  browser(browser) {
    browser
      .drupalInstall()
      .drupalInstallModule('navigation', true)
      .drupalInstallModule('big_pipe')
      .setWindowSize(1220, 800);
  },
  after(browser) {
    browser.drupalUninstall();
  },

  'Verify displace attribute': (browser) => {
    browser.drupalLoginAsAdmin(() => {
      browser
        .drupalRelativeURL('/admin/')
        .waitForElementPresent(
          '.admin-toolbar__displace-placeholder[data-offset-left]',
        );
    });
  },
};
