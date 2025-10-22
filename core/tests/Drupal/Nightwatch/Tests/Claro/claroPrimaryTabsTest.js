// This test is a duplicate of oliveroPrimaryTabsTest.js tagged for claro
const primaryTabsWrapper = '[data-drupal-nav-tabs]';
const activeTab = '.tabs__tab.is-active';
const inactiveTab = '.tabs__tab:not(.is-active)';
const mobileToggle = `${activeTab} .tabs__trigger`;
const hamburgerIcon = `${mobileToggle} .hamburger-icon`;
const closeIcon = `${mobileToggle} .close-icon`;

module.exports = {
  '@tags': ['core', 'claro'],
  before(browser) {
    browser
      .drupalInstall({
        setupFile:
          'core/tests/Drupal/TestSite/TestSiteClaroInstallTestScript.php',
        installProfile: 'minimal',
      })
      .drupalCreateUser({
        name: 'user',
        password: '123',
        permissions: ['administer nodes'],
      })
      .drupalLogin({ name: 'user', password: '123' });
    browser.window.setSize(1600, 800);
  },
  after(browser) {
    browser.drupalUninstall();
  },
  'Verify desktop primary tab display': (browser) => {
    browser
      .drupalRelativeURL('/node/1')
      .waitForElementVisible(primaryTabsWrapper)
      .assert.visible(activeTab)
      .assert.visible(inactiveTab)
      .assert.not.visible(mobileToggle);
  },
  'Verify mobile tab display and click functionality': (browser) => {
    browser.window
      .setSize(699, 800)
      .drupalRelativeURL('/node/1')
      .waitForElementVisible(primaryTabsWrapper)
      .assert.visible(activeTab)
      .assert.not.visible(inactiveTab)
      .assert.visible(mobileToggle)
      .assert.visible(hamburgerIcon)
      .assert.not.visible(closeIcon)
      .assert.attributeEquals(mobileToggle, 'aria-expanded', 'false')
      .assert.attributeEquals(hamburgerIcon, 'aria-hidden', 'false')
      .assert.attributeEquals(closeIcon, 'aria-hidden', 'true')
      .click(mobileToggle)
      .waitForElementVisible(inactiveTab)
      .assert.attributeEquals(mobileToggle, 'aria-expanded', 'true')
      .assert.not.visible(hamburgerIcon)
      .assert.visible(closeIcon)
      .assert.attributeEquals(hamburgerIcon, 'aria-hidden', 'true')
      .assert.attributeEquals(closeIcon, 'aria-hidden', 'false')
      .click(mobileToggle)
      .waitForElementNotVisible(inactiveTab)
      .assert.attributeEquals(mobileToggle, 'aria-expanded', 'false')
      .assert.visible(hamburgerIcon)
      .assert.not.visible(closeIcon)
      .assert.attributeEquals(hamburgerIcon, 'aria-hidden', 'false')
      .assert.attributeEquals(closeIcon, 'aria-hidden', 'true');
  },
};
