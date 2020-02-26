module.exports = {
  '@tags': ['core'],
  before(browser) {
    browser.drupalInstall({
      setupFile: 'core/tests/Drupal/TestSite/TestSiteInstallTestScript.php',
      langcode: 'fr',
    });
  },
  after(browser) {
    browser.drupalUninstall();
  },
  'Test page with langcode': browser => {
    browser
      .drupalRelativeURL('/test-page')
      .assert.attributeEquals('html', 'lang', 'fr')
      .drupalLogAndEnd({ onlyOnError: false });
  },
};
