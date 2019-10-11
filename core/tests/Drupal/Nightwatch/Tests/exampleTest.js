module.exports = {
  '@tags': ['core'],
  before(browser) {
    browser.drupalInstall({
      setupFile: 'core/tests/Drupal/TestSite/TestSiteInstallTestScript.php',
    });
  },
  after(browser) {
    browser.drupalUninstall();
  },
  'Test page': browser => {
    browser
      .drupalRelativeURL('/test-page')
      .waitForElementVisible('body', 1000)
      .assert.containsText('body', 'Test page text')
      .drupalLogAndEnd({ onlyOnError: false });
  },
  'Page objects test page': browser => {
    const testPage = browser.page.TestPage();

    testPage
      .drupalRelativeURL('/test-page')
      .waitForElementVisible('@body', testPage.props.timeout)
      .assert.containsText('@body', testPage.props.text)
      .assert.noDeprecationErrors()
      .drupalLogAndEnd({ onlyOnError: false });
  },
};
