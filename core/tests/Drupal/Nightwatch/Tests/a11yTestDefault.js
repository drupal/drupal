const argv = require('minimist')(process.argv.slice(2));

const a11yThemeTest = {
  '@tags': ['core', 'a11y', 'a11y:default'],

  before(browser) {
    browser.drupalInstall({ installProfile: 'nightwatch_a11y_testing' });
    // If the default theme is set to something other than Olivero, install it.
    if (
      argv.defaultTheme &&
      argv.defaultTheme !== browser.globals.defaultTheme
    ) {
      browser.drupalEnableTheme(argv.defaultTheme);
    }
  },
  after(browser) {
    browser.drupalUninstall();
  },
};

const testCases = [
  {
    name: 'Homepage',
    path: '/',
    // @todo remove the disabled 'region' rule in https://drupal.org/i/3318396.
    options: {
      rules: {
        region: { enabled: false },
      },
    },
  },
  {
    name: 'Login',
    path: '/user/login',
    // @todo remove the disabled 'region' rule in https://drupal.org/i/3318396.
    options: {
      rules: {
        region: { enabled: false },
      },
    },
  },
  // @todo remove the heading and duplicate id rules below in
  //   https://drupal.org/i/3318398.
  {
    name: 'Search',
    path: '/search/node',
    options: {
      rules: {
        'heading-order': { enabled: false },
        'duplicate-id-aria': { enabled: false },
      },
    },
  },
];

testCases.forEach((testCase) => {
  a11yThemeTest[`Accessibility - Default Theme: ${testCase.name}`] = (
    browser,
  ) => {
    browser
      .drupalRelativeURL(testCase.path)
      .axeInject()
      .axeRun('body', testCase.options || {});
  };
});

module.exports = a11yThemeTest;
