module.exports = {
  '@tags': ['core', 'olivero'],
  before(browser) {
    browser
      .drupalInstall({
        setupFile:
          'core/modules/search/tests/src/Nightwatch/Tests/Olivero/TestSiteOliveroInstallTestScript.php',
        installProfile: 'minimal',
      })
      .drupalLoginAsAdmin(() => {
        browser
          .drupalRelativeURL('/admin/structure/block')

          // Disable narrow search form block.
          .click(
            '[data-drupal-selector="edit-blocks-olivero-search-form-narrow-operations"] .dropbutton-toggle button',
          )
          .click('[href*="olivero_search_form_narrow/disable"]')

          // Disable wide search form block.
          .click(
            '[data-drupal-selector="edit-blocks-olivero-search-form-wide-operations"] .dropbutton-toggle button',
          )
          .click('[href*="olivero_search_form_wide/disable"]');
      });
  },
  after(browser) {
    browser.drupalUninstall();
  },
  'Verify no console errors': (browser) => {
    browser
      .drupalRelativeURL('/')
      .waitForElementVisible('body')
      .execute(
        // eslint-disable-next-line func-names, prefer-arrow-callback, no-shadow
        function () {
          return Drupal.errorLog.length === 0;
        },
        [],
        (result) => {
          browser.assert.ok(result.value, 'Verify no console errors exist.');
        },
      );
  },
};
