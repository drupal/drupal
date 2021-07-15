const mobileNavButtonSelector = 'button.mobile-nav-button';
const headerNavSelector = '#header-nav';
const linkSubMenuId = 'home-submenu-1';
const buttonSubMenuId = 'button-submenu-2';

/**
 * Sends arbitrary number of tab keys, and then checks that the last focused
 * element is within the given parent selector.
 *
 * @param {object} browser - Nightwatch Browser object
 * @param {string} parentSelector - Selector to which to test focused element against.
 * @param {number} tabCount - Amount of tab presses to send to browser
 * @param {boolean} [tabBackwards] - Hold down the SHIFT key when sending tabs
 */
const focusTrapCheck = (browser, parentSelector, tabCount, tabBackwards) => {
  if (tabBackwards === true) browser.keys(browser.Keys.SHIFT);
  for (let i = 0; i < tabCount; i++) {
    browser.keys(browser.Keys.TAB).pause(50);
  }
  browser
    .execute(
      // eslint-disable-next-line func-names, prefer-arrow-callback, no-shadow
      function (parentSelector) {
        // Verify focused element is still within the focus trap.
        return document.activeElement.matches(parentSelector);
      },
      [parentSelector],
      (result) => {
        browser.assert.ok(result.value);
      },
    )
    // Release all keys.
    .keys(browser.Keys.NULL);
};

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
    focusTrapCheck(
      browser,
      `${headerNavSelector} *, ${mobileNavButtonSelector}`,
      17,
    );
    focusTrapCheck(
      browser,
      `${headerNavSelector} *, ${mobileNavButtonSelector}`,
      19,
      true,
    );
  },
  'Verify parent <button> focus on ESC in narrow navigation': (browser) => {
    browser
      // Verify functionality on regular link's button.
      .drupalRelativeURL('/node')
      .waitForElementVisible('body')
      .click(mobileNavButtonSelector)
      .waitForElementVisible(headerNavSelector)
      .waitForElementVisible(`[aria-controls="${linkSubMenuId}"]`)
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
