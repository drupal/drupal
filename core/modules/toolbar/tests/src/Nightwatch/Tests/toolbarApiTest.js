/**
 * @file
 * Tests of the existing Toolbar JS Api.
 */

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
      .drupalLogin({ name: 'user', password: '123' });
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
      .waitForElementPresent('#toolbar-administration', 50000, 1000, false);
  },
  after(browser) {
    browser.drupalUninstall();
  },
  'Drupal.Toolbar.models': (browser) => {
    browser.execute(
      function () {
        const toReturn = {};
        const { models } = Drupal.toolbar;
        toReturn.hasMenuModel = models.hasOwnProperty('menuModel');
        toReturn.menuModelType = typeof models.menuModel === 'object';
        toReturn.hasToolbarModel = models.hasOwnProperty('toolbarModel');
        toReturn.toolbarModelType = typeof models.toolbarModel === 'object';
        toReturn.toolbarModelActiveTab =
          models.toolbarModel.get('activeTab').id ===
          'toolbar-item-administration';
        toReturn.toolbarModelActiveTray =
          models.toolbarModel.get('activeTray').id ===
          'toolbar-item-administration-tray';
        toReturn.toolbarModelIsOriented =
          models.toolbarModel.get('isOriented') === true;
        toReturn.toolbarModelIsFixed =
          models.toolbarModel.get('isFixed') === true;
        toReturn.toolbarModelAreSubtreesLoaded =
          models.toolbarModel.get('areSubtreesLoaded') === false;
        toReturn.toolbarModelIsViewportOverflowConstrained =
          models.toolbarModel.get('isViewportOverflowConstrained') === false;
        toReturn.toolbarModelOrientation =
          models.toolbarModel.get('orientation') === 'horizontal';
        toReturn.toolbarModelLocked =
          models.toolbarModel.get('locked') === null;
        toReturn.toolbarModelIsTrayToggleVisible =
          models.toolbarModel.get('isTrayToggleVisible') === true;
        toReturn.toolbarModelHeight = models.toolbarModel.get('height') === 79;
        toReturn.toolbarModelOffsetsBottom =
          models.toolbarModel.get('offsets').bottom === 0;
        toReturn.toolbarModelOffsetsLeft =
          models.toolbarModel.get('offsets').left === 0;
        toReturn.toolbarModelOffsetsRight =
          models.toolbarModel.get('offsets').right === 0;
        toReturn.toolbarModelOffsetsTop =
          models.toolbarModel.get('offsets').top === 79;
        toReturn.toolbarModelSubtrees =
          Object.keys(models.menuModel.get('subtrees')).length === 0;
        return toReturn;
      },
      [],
      (result) => {
        const expectedTrue = {
          hasMenuModel: 'has menu model',
          menuModelType: 'menu model is an object',
          hasToolbarModel: 'has toolbar model',
          toolbarModelType: 'toolbar model is an object',
          toolbarModelActiveTab: 'get("activeTab") has expected result',
          toolbarModelActiveTray: 'get("activeTray") has expected result',
          toolbarModelIsOriented: 'get("isOriented") has expected result',
          toolbarModelIsFixed: 'get("isFixed") has expected result',
          toolbarModelAreSubtreesLoaded:
            'get("areSubtreesLoaded") has expected result',
          toolbarModelIsViewportOverflowConstrained:
            'get("isViewportOverflowConstrained") has expected result',
          toolbarModelOrientation: 'get("orientation") has expected result',
          toolbarModelLocked: 'get("locked") has expected result',
          toolbarModelIsTrayToggleVisible:
            'get("isTrayToggleVisible") has expected result',
          toolbarModelHeight: 'get("height") has expected result',
          toolbarModelOffsetsBottom:
            'get("offsets") bottom has expected result',
          toolbarModelOffsetsLeft: 'get("offsets") left has expected result',
          toolbarModelOffsetsRight: 'get("offsets") right has expected result',
          toolbarModelOffsetsTop: 'get("offsets") top has expected result',
          toolbarModelSubtrees: 'get("subtrees") has expected result',
        };
        browser.assert.deepEqual(
          Object.keys(expectedTrue).sort(),
          Object.keys(result.value).sort(),
          'Keys to check match',
        );
        Object.keys(expectedTrue).forEach((property) => {
          browser.assert.equal(
            result.value[property],
            true,
            expectedTrue[property],
          );
        });
      },
    );
  },
  'Change tab': (browser) => {
    browser.execute(
      function () {
        const toReturn = {};
        const { models } = Drupal.toolbar;
        toReturn.hasMenuModel = models.hasOwnProperty('menuModel');
        toReturn.menuModelType = typeof models.menuModel === 'object';
        toReturn.hasToolbarModel = models.hasOwnProperty('toolbarModel');
        toReturn.toolbarModelType = typeof models.toolbarModel === 'object';

        const tab = document.querySelector('#toolbar-item-user');
        tab.dispatchEvent(new MouseEvent('click', { bubbles: true }));

        toReturn.toolbarModelChangedTab =
          models.toolbarModel.get('activeTab').id === 'toolbar-item-user';
        toReturn.toolbarModelChangedTray =
          models.toolbarModel.get('activeTray').id === 'toolbar-item-user-tray';
        return toReturn;
      },
      [],
      (result) => {
        const expectedTrue = {
          hasMenuModel: 'has menu model',
          menuModelType: 'menu model is an object',
          hasToolbarModel: 'has toolbar model',
          toolbarModelType: 'toolbar model is an object',
          toolbarModelChangedTab: 'get("activeTab") has expected result',
          toolbarModelChangedTray: 'get("activeTray") has expected result',
        };
        browser.assert.deepEqual(
          Object.keys(expectedTrue).sort(),
          Object.keys(result.value).sort(),
          'Keys to check match',
        );
        Object.keys(expectedTrue).forEach((property) => {
          browser.assert.equal(
            result.value[property],
            true,
            expectedTrue[property],
          );
        });
      },
    );
  },
  'Change orientation': (browser) => {
    browser.executeAsync(
      function (done) {
        const toReturn = {};
        const { models } = Drupal.toolbar;

        const orientationToggle = document.querySelector(
          '#toolbar-item-administration-tray .toolbar-toggle-orientation button',
        );
        toReturn.toolbarOrientation =
          models.toolbarModel.get('orientation') === 'horizontal';
        orientationToggle.dispatchEvent(
          new MouseEvent('click', { bubbles: true }),
        );
        setTimeout(() => {
          toReturn.toolbarChangeOrientation =
            models.toolbarModel.get('orientation') === 'vertical';
          done(toReturn);
        }, 100);
      },
      [],
      (result) => {
        const expectedTrue = {
          toolbarOrientation: 'get("orientation") has expected result',
          toolbarChangeOrientation: 'changing orientation has expected result',
        };
        browser.assert.deepEqual(
          Object.keys(expectedTrue).sort(),
          Object.keys(result.value).sort(),
          'Keys to check match',
        );
        Object.keys(expectedTrue).forEach((property) => {
          browser.assert.equal(
            result.value[property],
            true,
            expectedTrue[property],
          );
        });
      },
    );
  },
  'Open submenu': (browser) => {
    browser.executeAsync(
      function (done) {
        const toReturn = {};
        const { models } = Drupal.toolbar;
        Drupal.toolbar.models.toolbarModel.set('orientation', 'vertical');
        toReturn.toolbarOrientation =
          models.toolbarModel.get('orientation') === 'vertical';
        const manageTab = document.querySelector(
          '#toolbar-item-administration',
        );
        Drupal.toolbar.models.toolbarModel.set('activeTab', manageTab);
        const menuDropdown = document.querySelector(
          '#toolbar-item-administration-tray button',
        );
        menuDropdown.dispatchEvent(new MouseEvent('click', { bubbles: true }));

        setTimeout(() => {
          const statReportElement = document.querySelector(
            '#toolbar-link-system-status',
          );
          toReturn.submenuItem =
            statReportElement.textContent === 'Status report';
          done(toReturn);
        }, 100);
      },
      [],
      (result) => {
        const expectedTrue = {
          toolbarOrientation: 'get("orientation") has expected result',
          submenuItem: 'opening submenu has expected result',
        };
        browser.assert.deepEqual(
          Object.keys(expectedTrue).sort(),
          Object.keys(result.value).sort(),
          'Keys to check match',
        );
        Object.keys(expectedTrue).forEach((property) => {
          browser.assert.equal(
            result.value[property],
            true,
            expectedTrue[property],
          );
        });
      },
    );
  },
};
