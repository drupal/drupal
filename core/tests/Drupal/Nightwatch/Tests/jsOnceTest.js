module.exports = {
  '@tags': ['core'],
  before(browser) {
    browser.drupalInstall().drupalLoginAsAdmin(() => {
      browser
        .drupalRelativeURL('/admin/modules')
        .setValue('input[type="search"]', 'JS Once Test')
        .waitForElementVisible(
          'input[name="modules[js_once_test][enable]"]',
          1000,
        )
        .click('input[name="modules[js_once_test][enable]"]')
        .click('input[type="submit"]'); // Submit module form.
    });
  },
  after(browser) {
    browser.drupalUninstall();
  },
  'Test simple once call': (browser) => {
    browser
      .drupalRelativeURL('/js_once_test')
      .waitForElementVisible('[data-drupal-item]', 1000)
      // prettier-ignore
      .execute(
        function () {
          return once('js_once_test', '[data-drupal-item]');
        },
        (result) => {
          browser.assert.strictEqual(
            result.value.length,
            5,
            '5 items returned and "once-d"',
          );
        },
      )
      // Check that follow-up calls to once return an empty array.
      .execute(
        function () {
          return once('js_once_test', '[data-drupal-item]');
        },
        (result) => {
          browser.assert.strictEqual(
            result.value.length,
            0,
            '0 items returned',
          );
        },
      )
      .execute(
        function () {
          return once(
            'js_once_test_extra',
            '[data-drupal-item="1"],[data-drupal-item="2"]',
          );
        },
        (result) => {
          browser.assert.strictEqual(
            result.value.length,
            2,
            '2 items returned and "once-d"',
          );
        },
      )
      .execute(
        function () {
          return once(
            'js_once_test_extra',
            '[data-drupal-item="1"],[data-drupal-item="2"]',
          );
        },
        (result) => {
          browser.assert.strictEqual(
            result.value.length,
            0,
            '0 items returned',
          );
        },
      )
      .execute(
        function () {
          return once.remove('js_once_test', '[data-drupal-item]');
        },
        (result) => {
          browser.assert.strictEqual(
            result.value.length,
            5,
            '5 items returned and "de-once-d"',
          );
        },
      )
      .execute(
        function () {
          return once.remove('js_once_test', '[data-drupal-item]');
        },
        (result) => {
          browser.assert.strictEqual(
            result.value.length,
            0,
            '0 items returned',
          );
        },
      )
      .execute(
        function () {
          return once.remove(
            'js_once_test_extra',
            '[data-drupal-item="1"],[data-drupal-item="2"]',
          );
        },
        (result) => {
          browser.assert.strictEqual(
            result.value.length,
            2,
            '2 items returned and "de-once-d"',
          );
        },
      )
      .execute(
        function () {
          return once.remove(
            'js_once_test_extra',
            '[data-drupal-item="1"],[data-drupal-item="2"]',
          );
        },
        (result) => {
          browser.assert.strictEqual(
            result.value.length,
            0,
            '0 items returned',
          );
        },
      )
      .drupalLogAndEnd({ onlyOnError: false });
  },
};
