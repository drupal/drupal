const tableSelector = '#edit-field-multiple-value-form-field-wrapper table';
const tableHeaderSelector = '#edit-field-multiple-value-form-field-wrapper th';
const headerSelector = '#edit-field-multiple-value-form-field-wrapper h4';

module.exports = {
  '@tags': ['core', 'olivero'],
  before(browser) {
    browser
      .drupalInstall({
        setupFile:
          'core/tests/Drupal/TestSite/TestSiteOliveroInstallTestScript.php',
        installProfile: 'minimal',
      })
      .drupalCreateUser({
        name: 'user',
        password: '123',
        permissions: ['access site-wide contact form'],
      })
      .drupalLogin({ name: 'user', password: '123' });
  },
  after(browser) {
    browser.drupalUninstall();
  },
  'correct classes added to table and header': (browser) => {
    browser
      .setWindowSize(1400, 800)
      .drupalRelativeURL('/contact/olivero_test_contact_form')
      .waitForElementVisible(tableSelector, 1000)
      .assert.hasClass(tableSelector, [
        'tabledrag-disabled',
        'js-tabledrag-disabled',
      ])
      .assert.hasClass(tableHeaderSelector, 'is-disabled')
      .assert.hasClass(headerSelector, [
        'form-item__label',
        'form-item__label--multiple-value-form',
        'js-form-required',
        'form-required',
      ]);
  },
};
