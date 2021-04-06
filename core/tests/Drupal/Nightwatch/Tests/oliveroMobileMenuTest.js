const mobileNavButtonSelector = 'button.mobile-nav-button';
const headerNavSelector = '#header-nav';
const linkSubMenuId = 'home-submenu-1';
const buttonSubMenuId = 'button-submenu-2';

module.exports = {
  '@tags': ['core', 'olivero'],
  before(browser) {
    browser
      .drupalInstall({
        setupFile:
          'core/tests/Drupal/TestSite/TestSiteOliveroInstallTestScript.php',
        installProfile: 'minimal',
      })
      .resizeWindow(1000, 800);
  },
  after(browser) {
    browser.drupalUninstall();
  },
  'Verify mobile menu and submenu functionality': (browser) => {
    browser
      .drupalRelativeURL('/')
      .assert.not.visible(headerNavSelector)
      .click(mobileNavButtonSelector)
      .waitForElementVisible(headerNavSelector)
      // Test interactions for normal <a> menu links.
      .assert.not.visible(`#${linkSubMenuId}`)
      .assert.attributeEquals(
        `[aria-controls="${linkSubMenuId}"]`,
        'aria-expanded',
        'false',
      )
      .waitForElementVisible(`[aria-controls="${linkSubMenuId}"]`)
      .click(`[aria-controls="${linkSubMenuId}"]`)
      .waitForElementVisible(`#${linkSubMenuId}`)
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
  'Verify mobile menu focus trap': (browser) => {
    browser.drupalRelativeURL('/').click(mobileNavButtonSelector);
    // Send the tab key 17 times.
    // @todo test shift+tab functionality when
    // https://www.drupal.org/project/drupal/issues/3191077 is committed.
    for (let i = 0; i < 17; i++) {
      browser.keys(browser.Keys.TAB).pause(50);
    }

    // Ensure that focus trap keeps focused element within the navigation.
    browser.execute(
      // eslint-disable-next-line func-names, prefer-arrow-callback, no-shadow
      function (mobileNavButtonSelector, headerNavSelector) {
        // Verify focused element is still within the focus trap.
        return document.activeElement.matches(
          `${headerNavSelector} *, ${mobileNavButtonSelector}`,
        );
      },
      [mobileNavButtonSelector, headerNavSelector],
      (result) => {
        browser.assert.ok(result.value);
      },
    );
  },
};
