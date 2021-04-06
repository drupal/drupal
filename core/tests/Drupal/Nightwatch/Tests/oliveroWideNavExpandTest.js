module.exports = {
  '@tags': ['core', 'olivero'],
  before(browser) {
    browser.drupalInstall({
      setupFile:
        'core/tests/Drupal/TestSite/TestSiteOliveroInstallTestScript.php',
      installProfile: 'minimal',
    });
    browser.resizeWindow(1400, 800);
  },
  after(browser) {
    browser.drupalUninstall();
  },
  'On scroll, menu collapses to burger 🍔 menu': (browser) => {
    browser
      .drupalRelativeURL('/node')
      .assert.containsText(
        '#block-olivero-content h2',
        'Congratulations and welcome to the Drupal community!',
      )
      .assert.not.visible('button.wide-nav-expand')
      .getLocationInView('footer.site-footer', () => {
        browser.assert.visible('button.wide-nav-expand');
        browser.assert.not.visible('#site-header__inner');
      })
      .assert.not.visible('#block-olivero-main-menu')
      .click('button.wide-nav-expand', () => {
        browser.assert.visible('#block-olivero-main-menu');
      });
  },
};
