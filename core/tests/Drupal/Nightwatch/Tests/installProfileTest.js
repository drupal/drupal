module.exports = {
  '@tags': ['core'],
  before(browser) {
    browser.drupalInstall({
      setupFile: 'core/tests/Drupal/TestSite/TestSiteInstallTestScript.php',
      installProfile: 'demo_umami',
    });
  },
  after(browser) {
    browser.drupalUninstall();
  },
  'Test umami profile': browser => {
    browser
      .drupalRelativeURL('/test-page')
      .waitForElementVisible('body', 1000)
      .assert.elementPresent('#block-umami-branding')
      .drupalLogAndEnd({ onlyOnError: false });
  },
};
