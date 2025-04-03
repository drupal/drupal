const argv = require('minimist')(process.argv.slice(2));

const adminTest = {
  '@tags': ['core', 'a11y', 'a11y:admin'],

  before(browser) {
    browser.drupalInstall({ installProfile: 'nightwatch_a11y_testing' });
    // If an admin theme other than Claro is being used for testing, install it.
    if (argv.adminTheme && argv.adminTheme !== browser.globals.adminTheme) {
      browser.drupalEnableTheme(argv.adminTheme, true);
    }
  },
  after(browser) {
    browser.drupalUninstall();
  },
};
const testCases = [
  { name: 'User Edit', path: '/user/1/edit' },
  {
    name: 'Create Article',
    path: '/node/add/article?destination=/admin/content',
  },
  { name: 'Create Page', path: '/node/add/page?destination=/admin/content' },
  { name: 'Content Page', path: '/admin/content' },
  { name: 'Structure Page', path: '/admin/structure' },
  { name: 'Add content type', path: '/admin/structure/types/add' },
  { name: 'Add vocabulary', path: '/admin/structure/taxonomy/add' },
  // @todo remove the skipped rules below in https://drupal.org/i/3318394.
  {
    name: 'Structure | Block',
    path: '/admin/structure/block',
    options: {
      rules: {
        'color-contrast': { enabled: false },
        'duplicate-id-active': { enabled: false },
        region: { enabled: false },
      },
    },
  },
];

testCases.forEach((testCase) => {
  adminTest[`Accessibility - Admin Theme: ${testCase.name}`] = (browser) => {
    browser.drupalLoginAsAdmin(() => {
      browser
        .drupalRelativeURL(testCase.path)
        .axeInject()
        .axeRun('body', testCase.options || {});
    });
  };
});

module.exports = adminTest;
