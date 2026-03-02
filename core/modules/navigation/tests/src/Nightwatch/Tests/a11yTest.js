const navigationTest = {
  '@tags': ['core', 'a11y', 'a11y:admin', 'navigation'],

  before(browser) {
    browser
      .drupalInstall({
        installProfile: 'nightwatch_a11y_testing',
      })
      .drupalInstallModule('navigation', true);
  },
  after(browser) {
    browser.drupalUninstall();
  },
};
const testCases = [{ name: 'Claro page', path: '/user/1/edit' }];

testCases.forEach((testCase) => {
  navigationTest[`Accessibility - Navigation Module - ${testCase.name}`] = (
    browser,
  ) => {
    browser.drupalLoginAsAdmin(() => {
      browser.drupalRelativeURL(testCase.path).axeInject();

      // Wide viewport ("desktop") version.
      browser.setWindowSize(1220, 800).axeRun('body', testCase.options || {});

      // Narrow viewport ("mobile") version.
      browser
        .setWindowSize(1000, 800)
        .element.find({
          selector:
            '//*[@class="admin-toolbar-control-bar"]//button//*[text()="Expand sidebar"]',
          locateStrategy: 'xpath',
        })
        .waitUntil('visible')
        .click();

      browser
        .waitForElementVisible({
          selector: '//*[@id="admin-toolbar"]//button//*[text()="Create"]',
          locateStrategy: 'xpath',
        })
        .axeRun('body', testCase.options || {});
    });
  };
});

module.exports = navigationTest;
