const mobileNavButtonSelector = 'button.mobile-nav-button';
const headerNavSelector = '#header-nav';
const searchButtonSelector = 'button.header-nav__search-button';
const searchFormSelector = '.search-form.search-block-form';
const searchWideSelector = '.search-wide__wrapper';
const searchNarrowSelector = '.search-narrow__wrapper';

module.exports = {
  '@tags': ['core', 'olivero'],
  before(browser) {
    browser
      .drupalInstall({
        setupFile:
          'core/tests/Drupal/TestSite/TestSiteOliveroInstallTestScript.php',
        installProfile: 'minimal',
      })
      .drupalLoginAsAdmin(() => {
        browser
          .drupalRelativeURL('/admin/modules')
          .setValue('input[type="search"]', 'Search')
          .waitForElementVisible('input[name="modules[search][enable]"]', 1000)
          .click('input[name="modules[search][enable]"]')
          .click('input[type="submit"]'); // Submit module form.
        // Create user that can search.
      })
      .drupalCreateUser({
        name: 'user',
        password: '123',
        permissions: ['search content'],
      })
      .drupalLogin({ name: 'user', password: '123' });
  },
  after(browser) {
    browser.drupalUninstall();
  },
  'search wide form is accessible': (browser) => {
    browser
      .resizeWindow(1400, 800)
      .drupalRelativeURL('/')
      .click(searchButtonSelector)
      .waitForElementVisible(`${searchWideSelector} ${searchFormSelector}`);
  },
  'search narrow form is accessible': (browser) => {
    browser
      .resizeWindow(1000, 800)
      .drupalRelativeURL('/')
      .click(mobileNavButtonSelector)
      .waitForElementVisible(headerNavSelector)
      .waitForElementVisible(`${searchNarrowSelector} ${searchFormSelector}`);
  },
};
