module.exports = {
  '@tags': ['core'],
  before(browser) {
    browser.drupalInstall().drupalLoginAsAdmin(() => {
      browser
        .drupalRelativeURL('/admin/modules')
        .setValue('input[type="search"]', 'FormAPI')
        .waitForElementVisible('input[name="modules[form_test][enable]"]', 1000)
        .click('input[name="modules[form_test][enable]"]')
        .click('input[type="submit"]') // Submit module form.
        .click('input[type="submit"]'); // Confirm installation of dependencies.
    });
  },
  after(browser) {
    browser.drupalUninstall();
  },
  'Test form with state API': (browser) => {
    browser
      .drupalRelativeURL('/form-test/javascript-states-form')
      .waitForElementVisible('body', 1000)
      .waitForElementNotVisible('input[name="textfield"]', 1000)
      .assert.noDeprecationErrors();
  },
};
