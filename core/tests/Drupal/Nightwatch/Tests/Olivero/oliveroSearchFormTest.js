const mobileNavButtonSelector = 'button.mobile-nav-button';
const headerNavSelector = '#header-nav';
const searchButtonSelector = 'button.block-search-wide__button';
const searchFormSelector = '.search-form.search-block-form';
const searchWideSelector = '.block-search-wide__wrapper';
const searchWideInputSelector = '#edit-keys--2';
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
      .waitForElementVisible(searchButtonSelector)
      .assert.attributeEquals(searchButtonSelector, 'aria-expanded', 'false')
      .click(searchButtonSelector)
      .waitForElementVisible(searchWideInputSelector)
      .assert.attributeEquals(searchButtonSelector, 'aria-expanded', 'true')
      .assert.attributeContains(
        searchWideInputSelector,
        'placeholder',
        'Search by keyword or phrase.',
      )
      .assert.attributeContains(
        searchWideInputSelector,
        'title',
        'Enter the terms you wish to search for.',
      )
      .assert.elementPresent('button.search-form__submit')
      // Assert wide search form closes when element moves to body.
      .click('body')
      .waitForElementNotVisible(searchWideSelector)
      .assert.attributeEquals(searchButtonSelector, 'aria-expanded', 'false');
  },
  'Test focus management': (browser) => {
    browser
      .drupalRelativeURL('/')
      .waitForElementVisible(searchButtonSelector)
      .click(searchButtonSelector)
      .waitForElementVisible(searchWideInputSelector)
      .pause(400) // Wait for transitionend event to fire.
      // Assert that focus is moved to wide search text input.
      .execute(
        // eslint-disable-next-line func-names, prefer-arrow-callback, no-shadow
        function (searchWideInputSelector) {
          return document.activeElement.matches(searchWideInputSelector);
        },
        [searchWideInputSelector],
        (result) => {
          browser.assert.ok(
            result.value,
            'Assert that focus moves to wide search form on open.',
          );
        },
      )
      // Assert that search form is still visible when focus is on disclosure button.
      .keys(browser.Keys.SHIFT)
      .keys(browser.Keys.TAB)
      .pause(50)
      .isVisible(searchWideSelector)
      // Assert that search form is NOT visible when focus moves back to menu item.
      .keys(browser.Keys.TAB)
      .pause(50)
      .waitForElementNotVisible(searchWideSelector)
      // Release all keys.
      .keys(browser.Keys.NULL);
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
