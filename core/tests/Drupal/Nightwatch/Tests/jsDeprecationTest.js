module.exports = {
  '@tags': ['core'],
  before(browser) {
    browser.drupalInstall().drupalInstallModule('js_deprecation_test');
  },
  after(browser) {
    browser.drupalUninstall();
  },
  'Test JavaScript deprecations': (browser) => {
    browser
      .drupalRelativeURL('/js_deprecation_test')
      .waitForElementVisible('body', 1000)
      .assert.textContains('h1', 'JsDeprecationTest')
      .assert.deprecationErrorExists(
        'This function is deprecated for testing purposes.',
      )
      .assert.deprecationErrorExists(
        'This property is deprecated for testing purposes.',
      )
      .drupalLogAndEnd({ onlyOnError: false });
  },
};
