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
      .moveToElement(`[aria-controls="${linkSubMenuId}"]`, 0, 0)
      .assert.visible(`#${linkSubMenuId}`)
      .assert.attributeEquals(
        `[aria-controls="${linkSubMenuId}"]`,
        'aria-expanded',
        'true',
      )
      .assert.not.visible(`#${buttonSubMenuId}`)
      .moveToElement(`[aria-controls="${buttonSubMenuId}"]`, 0, 0)
      .assert.visible(`#${buttonSubMenuId}`)
      .assert.attributeEquals(
        `[aria-controls="${buttonSubMenuId}"]`,
        'aria-expanded',
        'true',
      );
  },
};
