/**
 * @file
 * Test the expected toolbar functionality.
 */

const itemAdministration = '#toolbar-item-administration';
const itemAdministrationTray = '#toolbar-item-administration-tray';
const adminOrientationButton = `${itemAdministrationTray} .toolbar-toggle-orientation button`;
const itemUser = '#toolbar-item-user';
const itemUserTray = '#toolbar-item-user-tray';
const userOrientationBtn = `${itemUserTray} .toolbar-toggle-orientation button`;

module.exports = {
  '@tags': ['core'],
  before(browser) {
    browser.drupalInstall({
      setupFile:
        'core/modules/toolbar/tests/src/Nightwatch/ToolbarTestSetup.php',
    });
  },
  beforeEach(browser) {
    // Set the resolution to the default desktop resolution. Ensure the default
    // toolbar is horizontal in headless mode.
    browser
      .setWindowSize(1920, 1080)
      // To clear active tab/tray from previous tests
      .execute(function () {
        localStorage.clear();
        // Clear escapeAdmin URL values.
        sessionStorage.clear();
      })
      .drupalRelativeURL('/')
      .waitForElementPresent('#toolbar-administration');
  },
  after(browser) {
    browser.drupalUninstall();
  },
  'Change tab': (browser) => {
    browser.waitForElementPresent(itemUserTray);
    browser.assert.not.hasClass(itemUser, 'is-active');
    browser.assert.not.hasClass(itemUserTray, 'is-active');
    browser.click(itemUser);
    browser.assert.hasClass(itemUser, 'is-active');
    browser.assert.hasClass(itemUserTray, 'is-active');
  },
  'Change orientation': (browser) => {
    browser.waitForElementPresent(adminOrientationButton);
    browser.assert.hasClass(
      itemAdministrationTray,
      'is-active toolbar-tray-horizontal',
    );
    browser.click(adminOrientationButton);
    browser.assert.hasClass(
      itemAdministrationTray,
      'is-active toolbar-tray-vertical',
    );
    browser.click(adminOrientationButton);
    browser.assert.hasClass(
      itemAdministrationTray,
      'is-active toolbar-tray-horizontal',
    );
  },
  'Toggle tray': (browser) => {
    browser.waitForElementPresent(itemUserTray);
    browser.click(itemUser);
    browser.assert.hasClass(itemUserTray, 'is-active');
    browser.click(itemUser);
    browser.assert.not.hasClass(itemUserTray, 'is-active');
    browser.click(itemUser);
    browser.assert.hasClass(itemUserTray, 'is-active');
  },
  'Toggle submenu and sub-submenu': (browser) => {
    browser.waitForElementPresent(adminOrientationButton);
    browser.assert.hasClass(
      itemAdministrationTray,
      'is-active toolbar-tray-horizontal',
    );
    browser.click(adminOrientationButton);
    browser.assert.hasClass(
      itemAdministrationTray,
      'is-active toolbar-tray-vertical',
    );
    browser.waitForElementPresent(
      '#toolbar-item-administration-tray li:nth-child(2) button',
    );
    browser.assert.not.hasClass(
      '#toolbar-item-administration-tray li:nth-child(2)',
      'open',
    );
    browser.assert.not.hasClass(
      '#toolbar-item-administration-tray li:nth-child(2) button',
      'open',
    );
    browser.click('#toolbar-item-administration-tray li:nth-child(2) button');
    browser.assert.hasClass(
      '#toolbar-item-administration-tray li:nth-child(2)',
      'open',
    );
    browser.assert.hasClass(
      '#toolbar-item-administration-tray li:nth-child(2) button',
      'open',
    );
    browser.expect
      .element('#toolbar-link-user-admin_index')
      .text.to.equal('People');
    browser.expect
      .element('#toolbar-link-system-admin_config_system')
      .text.to.equal('System');
    // Check sub-submenu.
    browser.waitForElementPresent(
      '#toolbar-item-administration-tray li.menu-item.level-2',
    );
    browser.assert.not.hasClass(
      '#toolbar-item-administration-tray li.menu-item.level-2',
      'open',
    );
    browser.assert.not.hasClass(
      '#toolbar-item-administration-tray li.menu-item.level-2 button',
      'open',
    );
    browser.click(
      '#toolbar-item-administration-tray li.menu-item.level-2 button',
    );
    browser.assert.hasClass(
      '#toolbar-item-administration-tray li.menu-item.level-2',
      'open',
    );
    browser.assert.hasClass(
      '#toolbar-item-administration-tray li.menu-item.level-2 button',
      'open',
    );
    browser.expect
      .element('#toolbar-link-entity-user-admin_form')
      .text.to.equal('Account settings');
  },
  'Narrow toolbar width breakpoint': (browser) => {
    browser.waitForElementPresent(adminOrientationButton);
    browser.assert.hasClass(
      itemAdministrationTray,
      'is-active toolbar-tray-horizontal',
    );
    browser.assert.hasClass('#toolbar-administration', 'toolbar-oriented');
    browser.window.setSize(263, 900);
    browser.assert.hasClass(
      itemAdministrationTray,
      'is-active toolbar-tray-vertical',
    );
    browser.assert.not.hasClass(itemAdministration, 'toolbar-oriented');
  },
  'Standard width toolbar breakpoint': (browser) => {
    browser.window.setSize(1000, 900);
    browser.waitForElementPresent(adminOrientationButton);
    browser.assert.hasClass('body', 'toolbar-fixed');
    browser.window.setSize(609, 900);
    browser.assert.hasClass(
      itemAdministrationTray,
      'is-active toolbar-tray-vertical',
    );
    browser.assert.not.hasClass('body', 'toolbar-fixed');
  },
  'Wide toolbar breakpoint': (browser) => {
    browser.waitForElementPresent(adminOrientationButton);
    browser.window.setSize(975, 900);
    browser.assert.hasClass(
      itemAdministrationTray,
      'is-active toolbar-tray-vertical',
    );
  },
  'Back to site link': (browser) => {
    const escapeSelector = '[data-toolbar-escape-admin]';
    browser.drupalRelativeURL('/user');
    browser.drupalRelativeURL('/admin');
    // Don't check the visibility as stark doesn't add the .path-admin class
    // to the <body> required to display the button.
    browser.assert.attributeContains(escapeSelector, 'href', '/user/login');
  },
  'Aural view test: tray orientation': (browser) => {
    browser.waitForElementPresent(
      '#toolbar-item-administration-tray .toolbar-toggle-orientation button',
    );
    browser.executeAsync(
      function (done) {
        Drupal.announce = done;

        const orientationButton = document.querySelector(
          '#toolbar-item-administration-tray .toolbar-toggle-orientation button',
        );
        orientationButton.dispatchEvent(
          new MouseEvent('click', { bubbles: true }),
        );
      },
      (result) => {
        browser.assert.equal(
          result.value,
          'Tray orientation changed to vertical.',
        );
      },
    );
    browser.executeAsync(
      function (done) {
        Drupal.announce = done;

        const orientationButton = document.querySelector(
          '#toolbar-item-administration-tray .toolbar-toggle-orientation button',
        );
        orientationButton.dispatchEvent(
          new MouseEvent('click', { bubbles: true }),
        );
      },
      (result) => {
        browser.assert.equal(
          result.value,
          'Tray orientation changed to horizontal.',
        );
      },
    );
  },
  'Aural view test: tray toggle': (browser) => {
    browser.executeAsync(
      function (done) {
        Drupal.announce = done;
        const $adminButton = jQuery('#toolbar-item-administration');
        $adminButton.trigger('click');
      },
      (result) => {
        browser.assert.equal(
          result.value,
          'Tray "Administration menu" closed.',
        );
      },
    );
    browser.executeAsync(
      function (done) {
        Drupal.announce = done;
        const $adminButton = jQuery('#toolbar-item-administration');
        $adminButton.trigger('click');
      },
      (result) => {
        browser.assert.equal(
          result.value,
          'Tray "Administration menu" opened.',
        );
      },
    );
  },
  'Toolbar event: drupalToolbarOrientationChange': (browser) => {
    browser.executeAsync(
      function (done) {
        jQuery(document).on(
          'drupalToolbarOrientationChange',
          function (event, orientation) {
            done(orientation);
          },
        );
        const orientationButton = document.querySelector(
          '#toolbar-item-administration-tray .toolbar-toggle-orientation button',
        );
        orientationButton.dispatchEvent(
          new MouseEvent('click', { bubbles: true }),
        );
      },
      (result) => {
        browser.assert.equal(result.value, 'vertical');
      },
    );
  },
  'Toolbar event: drupalToolbarTabChange': (browser) => {
    browser.executeAsync(
      function (done) {
        jQuery(document).on('drupalToolbarTabChange', function (event, tab) {
          done(tab.id);
        });
        jQuery('#toolbar-item-user').trigger('click');
      },
      (result) => {
        browser.assert.equal(result.value, 'toolbar-item-user');
      },
    );
  },
  'Toolbar event: drupalToolbarTrayChange': (browser) => {
    browser.executeAsync(
      function (done) {
        const $adminButton = jQuery('#toolbar-item-administration');
        // Hide the admin menu first, this event is not firing reliably
        // otherwise.
        $adminButton.trigger('click');
        jQuery(document).on('drupalToolbarTrayChange', function (event, tray) {
          done(tray.id);
        });
        $adminButton.trigger('click');
      },
      (result) => {
        browser.assert.equal(result.value, 'toolbar-item-administration-tray');
      },
    );
  },
  'Locked toolbar vertical wide viewport': (browser) => {
    browser.window.setSize(1000, 900);
    browser.waitForElementPresent(adminOrientationButton);
    // eslint-disable-next-line no-unused-expressions
    browser.expect.element(adminOrientationButton).to.be.visible;
    browser.window.setSize(975, 900);
    browser.assert.hasClass(
      itemAdministrationTray,
      'is-active toolbar-tray-vertical',
    );
    // eslint-disable-next-line no-unused-expressions
    browser.expect.element(adminOrientationButton).to.not.be.visible;
  },
  'Settings are retained on refresh': (browser) => {
    browser.waitForElementPresent(itemUser);
    // Set user as active tab.
    browser.assert.not.hasClass(itemUser, 'is-active');
    browser.assert.not.hasClass(itemUserTray, 'is-active');
    browser.click(itemUser);
    // Check tab and tray are open.
    browser.assert.hasClass(itemUser, 'is-active');
    browser.assert.hasClass(itemUserTray, 'is-active');
    // Set orientation to vertical.
    browser.waitForElementPresent(userOrientationBtn);
    browser.assert.hasClass(itemUserTray, 'is-active toolbar-tray-horizontal');
    browser.click(userOrientationBtn);
    browser.assert.hasClass(itemUserTray, 'is-active toolbar-tray-vertical');
    browser.refresh();
    // Check user tab is active.
    browser.assert.hasClass(itemUser, 'is-active');
    // Check tray is active and orientation is vertical.
    browser.assert.hasClass(itemUserTray, 'is-active toolbar-tray-vertical');
  },
  'Check toolbar overlap with page content': (browser) => {
    browser.assert.hasClass('body', 'toolbar-horizontal');
    browser.execute(
      () => {
        const toolbar = document.querySelector('#toolbar-administration');
        const nextElement = toolbar.nextElementSibling.getBoundingClientRect();
        const tray = document
          .querySelector('#toolbar-item-administration-tray')
          .getBoundingClientRect();
        // Page content should start after the toolbar height to not overlap.
        return nextElement.top > tray.top + tray.height;
      },
      (result) => {
        browser.assert.equal(
          result.value,
          true,
          'Toolbar and page content do not overlap',
        );
      },
    );
  },
};
