// cspell:ignore is-autocompleting

module.exports = {
  '@tags': ['core'],

  before(browser) {
    browser
      .drupalInstall()
      .drupalInstallModule('form_test', true)
      .drupalLoginAsAdmin(() => {
        browser
          .drupalRelativeURL('/admin/appearance')
          .click('[title="Install Claro as default theme"]')
          .waitForElementVisible('.system-themes-list', 10000); // Confirm installation.
      });
  },
  after(browser) {
    browser.drupalUninstall();
  },

  'Test Claro autocomplete': (browser) => {
    browser
      .drupalCreateUser({
        name: 'user',
        password: '123',
        permissions: ['access autocomplete test'],
      })
      .drupalLogin({ name: 'user', password: '123' })
      .drupalRelativeURL('/form-test/autocomplete')
      .waitForElementVisible('body', 1000);

    // Tests that entering a character from the
    // data-autocomplete-first-character-blacklist doesn't start the
    // autocomplete process.
    browser
      .setValue('[name="autocomplete_4"]', '/')
      .pause(1000)
      .waitForElementNotPresent('.is-autocompleting');

    // Tests both the autocomplete-message nor the autocomplete dropdown are
    // present when nothing has been entered in autocomplete-3.

    // eslint-disable-next-line no-unused-expressions
    browser.expect.element(
      '.js-form-item-autocomplete-3 [data-drupal-selector="autocomplete-message"]',
    ).to.not.visible;
    // eslint-disable-next-line no-unused-expressions
    browser.expect.element('#ui-id-3.ui-autocomplete').to.not.visible;

    // Tests that upon entering some text in autocomplete-3, first the
    // autocomplete-message appears and then the autocomplete dropdown with a
    // result. At that point the autocomplete-message should be invisible again.

    // eslint-disable-next-line no-unused-expressions
    browser
      .setValue('[name="autocomplete_3"]', '123')
      .waitForElementVisible(
        '.js-form-item-autocomplete-3 [data-drupal-selector="autocomplete-message"]',
      )
      .waitForElementVisible('#ui-id-3.ui-autocomplete')
      .expect.element(
        '.js-form-item-autocomplete-3 [data-drupal-selector="autocomplete-message"]',
      ).to.not.visible;

    browser.drupalLogAndEnd({ onlyOnError: false });
  },
};
