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

    browser
      .setValue('[name="autocomplete_4"]', '/')
      .pause(1000)
      .waitForElementNotPresent('.is-autocompleting');

    // eslint-disable-next-line no-unused-expressions
    browser.expect.element(
      '.js-form-item-autocomplete-3 [data-drupal-selector="autocomplete-message"]',
    ).to.not.visible;
    browser
      .setValue('[name="autocomplete_3"]', '123')
      .waitForElementVisible(
        '.js-form-item-autocomplete-3 [data-drupal-selector="autocomplete-message"]',
      )
      .drupalLogAndEnd({ onlyOnError: false });
  },
};
