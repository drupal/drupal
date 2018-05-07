module.exports = {
  '@tags': ['core'],
  before(browser) {
    browser
      .installDrupal({ setupFile: 'core/tests/Drupal/TestSite/TestSiteInstallTestScript.php' });
  },
  after(browser) {
    browser
      .uninstallDrupal();
  },
  'Test page': (browser) => {
    browser
      .relativeURL('/test-page')
      .waitForElementVisible('body', 1000)
      .assert.containsText('body', 'Test page text')
      .logAndEnd({ onlyOnError: false });
  },
  /**
  'Example failing test': (browser) => {
    browser
      // Change this to point at a site which has some console errors, as the
      // test site that was just installed doesn't.
      .url('https://www./')
      .waitForElementVisible('h1', 1000)
      // Wait for some errors to build up.
      .pause(5000)
      .assert.containsText('h1', 'I\'m the operator with my pocket calculator')
      .logAndEnd();
  },
  **/
};
