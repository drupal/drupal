const headerNavSelector = '#header-nav';
const linkSubMenuId = 'home-submenu-1';
const buttonSubMenuId = 'button-submenu-2';

module.exports = {
  '@tags': ['core', 'olivero'],
  before(browser) {
    browser.drupalInstall({
      setupFile:
        'core/tests/Drupal/TestSite/TestSiteOliveroInstallTestScript.php',
      installProfile: 'minimal',
    });
    browser.resizeWindow(1600, 800);
  },
  after(browser) {
    browser.drupalUninstall();
  },
  'Verify Olivero desktop menu click functionality': (browser) => {
    browser
      .drupalRelativeURL('/node')
      .waitForElementVisible(headerNavSelector)
      .assert.not.visible(`#${linkSubMenuId}`)
      .assert.attributeEquals(
        `[aria-controls="${linkSubMenuId}"]`,
        'aria-expanded',
        'false',
      )
      .click(`[aria-controls="${linkSubMenuId}"]`)
      .assert.visible(`#${linkSubMenuId}`)
      .assert.attributeEquals(
        `[aria-controls="${linkSubMenuId}"]`,
        'aria-expanded',
        'true',
      )

      // Test interactions for route:<button> menu links.
      .assert.not.visible(`#${buttonSubMenuId}`)
      .assert.attributeEquals(
        `[aria-controls="${buttonSubMenuId}"]`,
        'aria-expanded',
        'false',
      )
      .click(`[aria-controls="${buttonSubMenuId}"]`)
      .assert.visible(`#${buttonSubMenuId}`)
      .assert.attributeEquals(
        `[aria-controls="${buttonSubMenuId}"]`,
        'aria-expanded',
        'true',
      );
  },
  'Verify Olivero desktop menu hover functionality': (browser) => {
    browser
      .drupalRelativeURL('/node')
      .waitForElementVisible(headerNavSelector)
      .assert.visible(headerNavSelector)
      .moveToElement('link text', 'home')
      .assert.visible(`#${linkSubMenuId}`)
      .assert.attributeEquals(
        `[aria-controls="${linkSubMenuId}"]`,
        'aria-expanded',
        'true',
      )
      .moveToElement('link text', 'button')
      .assert.visible(`#${buttonSubMenuId}`)
      .assert.attributeEquals(
        `[aria-controls="${buttonSubMenuId}"]`,
        'aria-expanded',
        'true',
      );
  },
};
