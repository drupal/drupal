const headerNavSelector = '#header-nav';
const linkSubMenuId = 'primary-menu-item-1';
const buttonSubMenuId = 'primary-menu-item-12';

module.exports = {
  '@tags': ['core', 'olivero'],
  before(browser) {
    browser
      .drupalInstall({
        setupFile:
          'core/tests/Drupal/TestSite/TestSiteOliveroInstallTestScript.php',
        installProfile: 'minimal',
      })
      // Login and change max-nesting depth so we can verify that menu levels
      // greater than 2 do not break the site.
      .drupalLoginAsAdmin(() => {
        browser
          .drupalRelativeURL('/admin/structure/block/manage/olivero_main_menu')
          .waitForElementVisible('[data-drupal-selector="edit-settings-depth"]')
          .setValue('[data-drupal-selector="edit-settings-depth"]', 'Unlimited')
          .click('[data-drupal-selector="edit-actions-submit"]')
          .waitForElementVisible('body');
      });
    browser.window.setSize(1600, 800);
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
      // Verify tertiary menu item exists.
      .assert.visible('#primary-menu-item-11 .primary-nav__menu-link--level-3')
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
      .assert.not.visible(`#${linkSubMenuId}`)
      .moveToElement(`[aria-controls="${linkSubMenuId}"]`, 1, 1)
      .assert.visible(`#${linkSubMenuId}`)
      .assert.attributeEquals(
        `[aria-controls="${linkSubMenuId}"]`,
        'aria-expanded',
        'true',
      )
      .assert.not.visible(`#${buttonSubMenuId}`)
      .moveToElement(`[aria-controls="${buttonSubMenuId}"]`, 1, 1)
      .assert.visible(`#${buttonSubMenuId}`)
      .assert.attributeEquals(
        `[aria-controls="${buttonSubMenuId}"]`,
        'aria-expanded',
        'true',
      );
  },
  'Verify desktop menu converts to mobile if it gets too long': (browser) => {
    browser
      .drupalRelativeURL('/node')
      .waitForElementVisible('body')
      .assert.not.elementPresent('body.is-always-mobile-nav')
      .setWindowSize(1220, 800)
      .execute(() => {
        // Directly modify the width of the site branding name so that it causes
        // the primary navigation to be too long, and switch into mobile mode.
        document.querySelector('.site-branding__name').style.width = '350px';
      }, [])
      .assert.elementPresent('body.is-always-mobile-nav');
  },
};
