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
    browser
      .drupalInstall()
      .drupalInstallModule('breakpoint')
      .drupalInstallModule('toolbar')
      .drupalCreateUser({
        name: 'user',
        password: '123',
        permissions: [
          'access site reports',
          'access toolbar',
          'access administration pages',
          'administer menu',
          'administer modules',
          'administer site configuration',
          'administer account settings',
          'administer software updates',
          'access content',
          'administer permissions',
          'administer users',
        ],
      })
      .drupalLogin({ name: 'user', password: '123' })
      .drupalRelativeURL('/')
      .waitForElementPresent('#toolbar-administration', 10000);
  },
  beforeEach(browser) {
    browser.resizeWindow(1920, 1080);
    browser.execute(function () {
      // To clear active tab/tray from previous tests.
      localStorage.clear();
      // Clear escapeAdmin URL values.
      sessionStorage.clear();
    });
    browser.drupalRelativeURL('/');
  },
  after(browser) {
    browser.drupalUninstall();
  },
  'Change tab': (browser) => {
    browser.waitForElementPresent(itemUserTray);
    browser.assert.not.cssClassPresent(itemUser, 'is-active');
    browser.assert.not.cssClassPresent(itemUserTray, 'is-active');
    browser.click(itemUser);
    browser.assert.cssClassPresent(itemUser, 'is-active');
    browser.assert.cssClassPresent(itemUserTray, 'is-active');
  },
  'Change orientation': (browser) => {
    browser.waitForElementPresent(adminOrientationButton);
    browser.assert.cssClassPresent(
      itemAdministrationTray,
      'is-active toolbar-tray-horizontal',
    );
    browser.click(adminOrientationButton);
    browser.assert.cssClassPresent(
      itemAdministrationTray,
      'is-active toolbar-tray-vertical',
    );
    browser.click(adminOrientationButton);
    browser.assert.cssClassPresent(
      itemAdministrationTray,
      'is-active toolbar-tray-horizontal',
    );
  },
  'Toggle tray': (browser) => {
    browser.waitForElementPresent(itemUserTray);
    browser.click(itemUser);
    browser.assert.cssClassPresent(itemUserTray, 'is-active');
    browser.click(itemUser);
    browser.assert.not.cssClassPresent(itemUserTray, 'is-active');
    browser.click(itemUser);
    browser.assert.cssClassPresent(itemUserTray, 'is-active');
  },
  'Toggle submenu and sub-submenu': (browser) => {
    browser.waitForElementPresent(adminOrientationButton);
    browser.assert.cssClassPresent(
      itemAdministrationTray,
      'is-active toolbar-tray-horizontal',
    );
    browser.click(adminOrientationButton);
    browser.assert.cssClassPresent(
      itemAdministrationTray,
      'is-active toolbar-tray-vertical',
    );
    browser.waitForElementPresent(
      '#toolbar-item-administration-tray li:nth-child(4) button',
    );
    browser.assert.not.cssClassPresent(
      '#toolbar-item-administration-tray li:nth-child(4)',
      'open',
    );
    browser.assert.not.cssClassPresent(
      '#toolbar-item-administration-tray li:nth-child(4) button',
      'open',
    );
    browser.click('#toolbar-item-administration-tray li:nth-child(4) button');
    browser.assert.cssClassPresent(
      '#toolbar-item-administration-tray li:nth-child(4)',
      'open',
    );
    browser.assert.cssClassPresent(
      '#toolbar-item-administration-tray li:nth-child(4) button',
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
    browser.assert.not.cssClassPresent(
      '#toolbar-item-administration-tray li.menu-item.level-2',
      'open',
    );
    browser.assert.not.cssClassPresent(
      '#toolbar-item-administration-tray li.menu-item.level-2 button',
      'open',
    );
    browser.click(
      '#toolbar-item-administration-tray li.menu-item.level-2 button',
    );
    browser.assert.cssClassPresent(
      '#toolbar-item-administration-tray li.menu-item.level-2',
      'open',
    );
    browser.assert.cssClassPresent(
      '#toolbar-item-administration-tray li.menu-item.level-2 button',
      'open',
    );
    browser.expect
      .element('#toolbar-link-entity-user-admin_form')
      .text.to.equal('Account settings');
  },
  'Narrow toolbar width breakpoint': (browser) => {
    browser.waitForElementPresent(adminOrientationButton);
    browser.assert.cssClassPresent(
      itemAdministrationTray,
      'is-active toolbar-tray-horizontal',
    );
    browser.assert.cssClassPresent(
      '#toolbar-administration',
      'toolbar-oriented',
    );
    browser.resizeWindow(263, 900);
    browser.assert.cssClassPresent(
      itemAdministrationTray,
      'is-active toolbar-tray-vertical',
    );
    browser.assert.not.cssClassPresent(itemAdministration, 'toolbar-oriented');
  },
  'Standard width toolbar breakpoint': (browser) => {
    browser.resizeWindow(1000, 900);
    browser.waitForElementPresent(adminOrientationButton);
    browser.assert.cssClassPresent('body', 'toolbar-fixed');
    browser.resizeWindow(609, 900);
    browser.assert.cssClassPresent(
      itemAdministrationTray,
      'is-active toolbar-tray-vertical',
    );
    browser.assert.not.cssClassPresent('body', 'toolbar-fixed');
  },
  'Wide toolbar breakpoint': (browser) => {
    browser.waitForElementPresent(adminOrientationButton);
    browser.resizeWindow(975, 900);
    browser.assert.cssClassPresent(
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
    browser.assert.attributeContains(escapeSelector, 'href', '/user/2');
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
    browser.resizeWindow(1000, 900);
    browser.waitForElementPresent(adminOrientationButton);
    // eslint-disable-next-line no-unused-expressions
    browser.expect.element(adminOrientationButton).to.be.visible;
    browser.resizeWindow(975, 900);
    browser.assert.cssClassPresent(
      itemAdministrationTray,
      'is-active toolbar-tray-vertical',
    );
    // eslint-disable-next-line no-unused-expressions
    browser.expect.element(adminOrientationButton).to.not.be.visible;
  },
  'Settings are retained on refresh': (browser) => {
    browser.waitForElementPresent(itemUser);
    // Set user as active tab.
    browser.assert.not.cssClassPresent(itemUser, 'is-active');
    browser.assert.not.cssClassPresent(itemUserTray, 'is-active');
    browser.click(itemUser);
    // Check tab and tray are open.
    browser.assert.cssClassPresent(itemUser, 'is-active');
    browser.assert.cssClassPresent(itemUserTray, 'is-active');
    // Set orientation to vertical.
    browser.waitForElementPresent(userOrientationBtn);
    browser.assert.cssClassPresent(
      itemUserTray,
      'is-active toolbar-tray-horizontal',
    );
    browser.click(userOrientationBtn);
    browser.assert.cssClassPresent(
      itemUserTray,
      'is-active toolbar-tray-vertical',
    );
    browser.refresh();
    // Check user tab is active.
    browser.assert.cssClassPresent(itemUser, 'is-active');
    // Check tray is active and orientation is vertical.
    browser.assert.cssClassPresent(
      itemUserTray,
      'is-active toolbar-tray-vertical',
    );
  },
  'Check toolbar overlap with page content': (browser) => {
    browser.assert.cssClassPresent('body', 'toolbar-horizontal');
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
