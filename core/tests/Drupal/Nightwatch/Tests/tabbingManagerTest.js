module.exports = {
  '@tags': ['core'],
  before(browser) {
    browser.drupalInstall().drupalLoginAsAdmin(() => {
      browser
        .drupalRelativeURL('/admin/modules')
        .setValue('input[type="search"]', 'Tabbing Manager Test')
        .waitForElementVisible(
          'input[name="modules[tabbingmanager_test][enable]"]',
          1000,
        )
        .click('input[name="modules[tabbingmanager_test][enable]"]')
        .click('input[type="submit"]');
    });
  },
  after(browser) {
    browser.drupalUninstall();
  },
  'test tabbingmanager': (browser) => {
    browser
      .drupalRelativeURL('/tabbingmanager-test')
      .waitForElementPresent('#tabbingmanager-test-container', 1000);

    // Tab through the form without tabbing constrained. Tabbing out of the
    // third input should focus the fourth.
    browser
      .execute(
        // eslint-disable-next-line func-names, prefer-arrow-callback
        function () {
          document.querySelector('#first').focus();
          return document.activeElement.id;
        },
        [],
        (result) => {
          browser.assert.equal(
            result.value,
            'first',
            '[not constrained] First element focused after calling focus().',
          );
        },
      )
      .setValue('#first', [browser.Keys.TAB])
      .execute(
        // eslint-disable-next-line func-names, prefer-arrow-callback
        function () {
          return document.activeElement.id;
        },
        [],
        (result) => {
          browser.assert.equal(
            result.value,
            'second',
            '[not constrained] Tabbing first element focuses second element.',
          );
        },
      )
      .setValue('#second', [browser.Keys.TAB])
      .execute(
        // eslint-disable-next-line func-names, prefer-arrow-callback
        function () {
          return document.activeElement.id;
        },
        [],
        (result) => {
          browser.assert.equal(
            result.value,
            'third',
            '[not constrained] Tabbing second element focuses third element.',
          );
        },
      )
      .setValue('#third', [browser.Keys.TAB])
      .execute(
        // eslint-disable-next-line func-names, prefer-arrow-callback
        function () {
          return document.activeElement.id;
        },
        [],
        (result) => {
          browser.assert.equal(
            result.value,
            'fourth',
            '[not constrained] Tabbing third element focuses fourth element.',
          );
        },
      );

    // Tab through the form with tabbing constrained to the container that has
    // the first, second, and third inputs. Tabbing out of the third (final)
    // input should move focus back to the first one.
    browser
      .execute(
        // eslint-disable-next-line func-names, prefer-arrow-callback
        function () {
          Drupal.tabbingManager.constrain(
            document.querySelector('#tabbingmanager-test-container'),
            { trapFocus: true },
          );
          document.querySelector('#first').focus();
          return document.activeElement.id;
        },
        [],
        (result) => {
          browser.assert.equal(
            result.value,
            'first',
            '[constrained] First element focused after calling focus().',
          );
        },
      )
      .setValue('#first', [browser.Keys.TAB])
      .execute(
        // eslint-disable-next-line func-names, prefer-arrow-callback
        function () {
          return document.activeElement.id;
        },
        [],
        (result) => {
          browser.assert.equal(
            result.value,
            'second',
            '[constrained] Tabbing first element focuses second element',
          );
        },
      )
      .setValue('#second', [browser.Keys.TAB])
      .execute(
        // eslint-disable-next-line func-names, prefer-arrow-callback
        function () {
          return document.activeElement.id;
        },
        [],
        (result) => {
          browser.assert.equal(
            result.value,
            'third',
            '[constrained] Tabbing second element focuses the third.',
          );
        },
      )
      .setValue('#third', [browser.Keys.TAB])
      .execute(
        // eslint-disable-next-line func-names, prefer-arrow-callback
        function () {
          return document.activeElement.id;
        },
        [],
        (result) => {
          browser.assert.equal(
            result.value,
            'first',
            '[constrained] Tabbing final element focuses the first.',
          );
        },
      );

    // Confirm shift+tab on the first element focuses the third (final).
    browser
      .execute(
        // eslint-disable-next-line func-names, prefer-arrow-callback
        function () {
          document.querySelector('#first').focus();
          return document.activeElement.id;
        },
        [],
        (result) => {
          browser.assert.equal(
            result.value,
            'first',
            '[constrained] First element focused after calling focus().',
          );
        },
      )
      .setValue('#first', [browser.Keys.SHIFT, browser.Keys.TAB])
      .execute(
        // eslint-disable-next-line func-names, prefer-arrow-callback
        function () {
          return document.activeElement.id;
        },
        [],
        (result) => {
          browser.assert.equal(
            result.value,
            'third',
            '[constrained] Shift+tab the first element moves focus to the last element.',
          );
        },
      );

    browser.drupalLogAndEnd({ onlyOnError: false });
  },
};
