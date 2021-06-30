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
      // check that follow-up calls to once return an empty array.
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
  'Test BC layer with jquery once calls': (browser) => {
    browser
      .drupalRelativeURL('/js_once_with_bc_test')
      .waitForElementVisible('[data-drupal-item]', 1000)
      // prettier-ignore
      .execute(
        function () {
          // A core script calls once on some elements.
          once('js_once_test', '[data-drupal-item]');
          // a contrib module calls jquery once before they update to
          // @drupal/once
          return jQuery('[data-drupal-item]').once('js_once_test');
        },
        (result) => {
          browser.assert.strictEqual(
            result.value.length,
            0,
            'Calls to once() are taken into account when using jQuery.once()',
          );
        },
      )
      // once calls don't take into account calls to jquery.once by design.
      .execute(
        function () {
          // Calling jquery once before @drupal/once will lead to duplicate
          // processing.
          jQuery('[data-drupal-item]').once('js_once_test_extra');
          // A core script calls once on some elements.
          return once('js_once_test_extra', '[data-drupal-item]');
        },
        (result) => {
          browser.assert.strictEqual(
            result.value.length,
            5,
            '5 items returned by once() after a call to jQuery.once()',
          );
        },
      )
      .execute(
        function () {
          // A core script calls once on some elements.
          once.remove('js_once_test', '[data-drupal-item]');
          // a contrib module calls jquery once before they update to
          // @drupal/once
          return jQuery('[data-drupal-item]').removeOnce('js_once_test');
        },
        (result) => {
          browser.assert.strictEqual(
            result.value.length,
            0,
            'Calls to once.remove() are taken into account when using jQuery.removeOnce()',
          );
        },
      )
      .drupalLogAndEnd({ onlyOnError: false });
  },
};
