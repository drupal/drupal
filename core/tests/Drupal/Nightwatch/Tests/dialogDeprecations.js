const dialogDeprecationsTest = {
  '@tags': ['core', 'dialog'],

  before(browser) {
    browser
      .drupalInstall()
      .drupalInstallModule('block')
      .drupalInstallModule('js_deprecation_test')
      .drupalInstallModule('js_testing_log_test');
  },
  after(browser) {
    browser.drupalUninstall();
  },

  'jQuery Events Deprecation Tests': (browser) => {
    browser.drupalLoginAsAdmin(() => {
      browser
        .drupalRelativeURL('/admin/structure/block')
        .waitForElementVisible('body', 1000)
        .execute(function () {
          const button = document.querySelector(
            '[data-drupal-selector="edit-blocks-region-sidebar-first-title"]',
          );
          button.click();
          setTimeout(() => {
            window.jQuery('.ui-dialog-content').trigger('dialogButtonsChange');
          }, 100);
        })
        .assert.deprecationErrorExists(
          'jQuery event dialogButtonsChange is deprecated in 11.2.0 and is removed from Drupal:12.0.0. See https://www.drupal.org/node/3464202',
        );
    });
  },
};

module.exports = dialogDeprecationsTest;
