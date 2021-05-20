const buttonSelector = 'button.sticky-header-toggle';
const mainMenuSelector = '#block-olivero-main-menu';

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
      .assert.not.visible(buttonSelector)
      .assert.attributeEquals(buttonSelector, 'aria-checked', 'false')
      .getLocationInView('footer.site-footer', () => {
        browser.assert.visible(buttonSelector);
        browser.assert.not.visible('#site-header__inner');
      })
      .assert.not.visible(mainMenuSelector)
      .click(buttonSelector)
      .assert.visible(mainMenuSelector)
      .assert.attributeEquals(buttonSelector, 'aria-checked', 'true')

      // Sticky header should remain open after page reload in open state.
      .drupalRelativeURL('/node')
      .assert.visible(mainMenuSelector)
      .assert.attributeEquals(buttonSelector, 'aria-checked', 'true');
  },
};
