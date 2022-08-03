module.exports = {
  '@tags': ['core', 'ajax'],
  before(browser) {
    browser.drupalInstall().drupalLoginAsAdmin(() => {
      browser
        .drupalRelativeURL('/admin/modules')
        .setValue('input[type="search"]', 'Ajax test')
        .waitForElementVisible('input[name="modules[ajax_test][enable]"]', 1000)
        .click('input[name="modules[ajax_test][enable]"]')
        .submitForm('input[type="submit"]') // Submit module form.
        .waitForElementVisible(
          '.system-modules-confirm-form input[value="Continue"]',
          2000,
        )
        .submitForm('input[value="Continue"]') // Confirm installation of dependencies.
        .waitForElementVisible('.system-modules', 10000);
    });
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
