module.exports = {
  '@tags': ['core'],
  before(browser) {
    browser.drupalInstall().drupalLoginAsAdmin(() => {
      browser
        .drupalRelativeURL('/admin/modules')
        .setValue('input[type="search"]', 'JS Deprecation test')
        .waitForElementVisible(
          'input[name="modules[js_deprecation_test][enable]"]',
          1000,
        )
        .click('input[name="modules[js_deprecation_test][enable]"]')
        .click('input[type="submit"]'); // Submit module form.
    });
  },
  after(browser) {
    browser.drupalUninstall();
  },
  'Test JavaScript deprecations': (browser) => {
    browser
      .drupalRelativeURL('/js_deprecation_test')
      .waitForElementVisible('body', 1000)
      .assert.containsText('h1', 'JsDeprecationTest')
      .assert.deprecationErrorExists(
        'This function is deprecated for testing purposes.',
      )
      .assert.deprecationErrorExists(
        'This property is deprecated for testing purposes.',
      )
      .drupalLogAndEnd({ onlyOnError: false });
  },
};
