module.exports = {
  '@tags': ['core', 'ajax'],
  before(browser) {
    browser.drupalInstall().drupalInstallModule('ajax_test', true);
  },
  after(browser) {
    browser.drupalUninstall();
  },
  'Test Execution Order': (browser) => {
    browser
      .drupalRelativeURL('/ajax-test/promise-form')
      .waitForElementVisible('body', 1000)
      .click('[data-drupal-selector="edit-test-execution-order-button"]')
      .waitForElementVisible('#ajax_test_form_promise_wrapper', 1000)
      .assert.containsText(
        '#ajax_test_form_promise_wrapper',
        '12345',
        'Ajax commands execution order confirmed',
      );
  },
};
