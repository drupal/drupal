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
  'Verify secondary navigation close on blur': (browser) => {
    browser
      .drupalRelativeURL('/node')
      .waitForElementVisible(headerNavSelector)
      .click(`[aria-controls="${linkSubMenuId}"]`)
      .assert.attributeEquals(
        `[aria-controls="${linkSubMenuId}"]`,
        'aria-expanded',
        'true',
      );
    browser
      .execute(
        // eslint-disable-next-line func-names, prefer-arrow-callback, no-shadow
        function () {
          document.querySelector('.site-branding__name a').focus();
        },
        [],
      )
      .pause(400)
      .assert.attributeEquals(
        `[aria-controls="${linkSubMenuId}"]`,
        'aria-expanded',
        'false',
      );
  },
  'Verify parent <button> focus on ESC in wide navigation': (browser) => {
    browser
      // Verify functionality on regular link's button.
      .drupalRelativeURL('/node')
      .waitForElementVisible(headerNavSelector)
      .click(`[aria-controls="${linkSubMenuId}"]`)
      .waitForElementVisible(`#${linkSubMenuId}`)
      .keys(browser.Keys.TAB)
      .pause(50)
      .execute(
        // eslint-disable-next-line func-names, prefer-arrow-callback, no-shadow
        function (linkSubMenuId) {
          return document.activeElement.matches(`#${linkSubMenuId} *`);
        },
        [linkSubMenuId],
        (result) => {
          browser.assert.ok(result.value);
        },
      )
      .keys(browser.Keys.ESCAPE)
      .pause(50)
      .execute(
        // eslint-disable-next-line func-names, prefer-arrow-callback, no-shadow
        function (linkSubMenuId) {
          return document.activeElement.matches(
            `[aria-controls="${linkSubMenuId}"]`,
          );
        },
        [linkSubMenuId],
        (result) => {
          browser.assert.ok(result.value);
        },
      )
      // Verify functionality on route:<button> button.
      .click(`[aria-controls="${buttonSubMenuId}"]`)
      .waitForElementVisible(`#${buttonSubMenuId}`)
      .keys(browser.Keys.TAB)
      .pause(50)
      .execute(
        // eslint-disable-next-line func-names, prefer-arrow-callback, no-shadow
        function (buttonSubMenuId) {
          return document.activeElement.matches(`#${buttonSubMenuId} *`);
        },
        [buttonSubMenuId],
        (result) => {
          browser.assert.ok(result.value);
        },
      )
      .keys(browser.Keys.ESCAPE)
      .pause(50)
      .execute(
        // eslint-disable-next-line func-names, prefer-arrow-callback, no-shadow
        function (buttonSubMenuId) {
          return document.activeElement.matches(
            `[aria-controls="${buttonSubMenuId}"]`,
          );
        },
        [buttonSubMenuId],
        (result) => {
          browser.assert.ok(result.value);
        },
      );
  },
};
