const navigationTest = {
  '@tags': ['core', 'a11y', 'a11y:admin', 'navigation'],

  before(browser) {
    browser
      .drupalInstall({
        installProfile: 'nightwatch_a11y_testing',
      })
      .drupalInstallModule('navigation', true)
      .setWindowSize(1220, 800);
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
      browser
        .drupalRelativeURL(testCase.path)
        .axeInject()
        .axeRun('body', testCase.options || {});
    });
  };
});

module.exports = navigationTest;
