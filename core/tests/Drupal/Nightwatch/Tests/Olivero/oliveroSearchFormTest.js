const mobileNavButtonSelector = 'button.mobile-nav-button';
const headerNavSelector = '#header-nav';
const searchButtonSelector = 'button.block-search-wide__button';
const searchFormSelector = '.search-form.search-block-form';
const searchWideSelector = '.block-search-wide__wrapper';
const searchNarrowSelector = '.block-search-narrow';

module.exports = {
  '@tags': ['core', 'olivero'],
  before(browser) {
    browser
      .drupalInstall({
        setupFile:
          'core/tests/Drupal/TestSite/TestSiteOliveroInstallTestScript.php',
        installProfile: 'minimal',
      })
      // Create user that can search.
      .drupalCreateUser({
        name: 'user',
        password: '123',
        permissions: ['search content', 'use advanced search'],
      })
      .drupalLogin({ name: 'user', password: '123' });
  },
  after(browser) {
    browser.drupalUninstall();
  },
  'search wide form is accessible and altered': (browser) => {
    browser
      .resizeWindow(1400, 800)
      .drupalRelativeURL('/')
      .click(searchButtonSelector)
      .waitForElementVisible(`${searchWideSelector}`)
      .waitForElementVisible(`${searchWideSelector} ${searchFormSelector}`)
      .assert.attributeContains(
        `${searchWideSelector} ${searchFormSelector} input[name=keys]`,
        'placeholder',
        'Search by keyword or phrase.',
      )
      .assert.attributeContains(
        `${searchWideSelector} ${searchFormSelector} input[name=keys]`,
        'title',
        'Enter the terms you wish to search for.',
      )
      .assert.elementPresent('button.search-form__submit');
  },
  'search narrow form is accessible': (browser) => {
    browser
      .resizeWindow(1000, 800)
      .drupalRelativeURL('/')
      .click(mobileNavButtonSelector)
      .waitForElementVisible(headerNavSelector)
      .waitForElementVisible(`${searchNarrowSelector} ${searchFormSelector}`);
  },
  'submit button styled as primary on forms with <= 2 actions': (browser) => {
    browser
      .resizeWindow(1400, 800)
      .drupalRelativeURL('/form-test/object-controller-builder')
      .assert.elementPresent(
        '#edit-actions input[type=submit].button--primary',
      );
  },
  'search page is altered': (browser) => {
    browser
      .resizeWindow(1400, 800)
      .drupalRelativeURL('/search')
      .assert.attributeContains(
        '.search-form input[name=keys]',
        'placeholder',
        'Search by keyword or phrase.',
      )
      .assert.attributeContains(
        '.search-form input[name=keys]',
        'title',
        'Enter the terms you wish to search for.',
      )
      .assert.elementPresent('#edit-basic input[type=submit].button--primary')
      .assert.elementPresent(
        '#edit-advanced input[type=submit].button--primary',
      );
  },
};
