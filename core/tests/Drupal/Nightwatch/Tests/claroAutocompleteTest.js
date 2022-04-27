module.exports = {
  '@tags': ['core'],

  before(browser) {
    browser.drupalInstall().drupalLoginAsAdmin(() => {
      browser
        .drupalRelativeURL('/admin/modules')
        .setValue('input[type="search"]', 'FormAPI Test')
        .waitForElementVisible('input[name="modules[form_test][enable]"]', 1000)
        .click('input[name="modules[form_test][enable]"]')
        .submitForm('input[type="submit"]') // Submit module form.
        .waitForElementVisible(
          '.system-modules-confirm-form input[value="Continue"]',
          2000,
        )
        .submitForm('input[value="Continue"]') // Confirm installation of dependencies.
        .waitForElementVisible('.system-modules', 10000);

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
