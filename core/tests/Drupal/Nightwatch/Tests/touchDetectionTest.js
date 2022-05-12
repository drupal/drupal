module.exports = {
  '@tags': ['core'],
  before(browser) {
    browser
      .drupalInstall()
      .drupalInstallModule('modernizr_deprecation_test')
      .drupalLoginAsAdmin(() => {
        browser
          .drupalRelativeURL('/admin/config/development/performance')
          .click('#edit-clear')
          .waitForElementVisible('[data-drupal-messages]', 1000)
          .assert.containsText('body', 'Caches cleared')
          .execute(() => {
            sessionStorage.setItem(
              'js_testing_log_test.warnings',
              JSON.stringify([]),
            );
          });
      });
  },
  after(browser) {
    browser.drupalUninstall();
  },
  'Touchevents from core library': (browser) => {
    browser
      .drupalRelativeURL('/load-a-library/core/drupal.touchevents-test')
      .assert.containsText('body', 'Attaching core/drupal.touchevents-test')
      .waitForElementVisible('html.no-touchevents')
      .assert.noDeprecationErrors();
  },
  'drupal.touchevents.test is used if loaded alongside Modernizr': (
    browser,
  ) => {
    browser
      .drupalRelativeURL(
        '/load-a-library/modernizr_deprecation_test/modernizr_and_touchevents',
      )
      .waitForElementVisible('html.no-touchevents')
      .findElement('script[src*="touchevents-test.js"]')
      .findElement('script[src*="modernizr.min.js"]')
      .assert.containsText(
        'body',
        'Attaching modernizr_deprecation_test/modernizr_and_touchevents',
      )
      .assert.noDeprecationErrors();
  },
  'Touchevents from Modernizr': (browser) => {
    browser.drupalLoginAsAdmin(() => {
      browser
        .drupalRelativeURL('/load-a-library/core/modernizr')
        .waitForElementVisible('html.no-touchevents', 1000)
        .assert.containsText('body', 'Attaching core/modernizr')
        .assert.deprecationErrorExists(
          'The Modernizr touch events test is deprecated in Drupal 9.4.0 and will be removed in Drupal 10.0.0. See https://www.drupal.org/node/3277381 for information on its replacement and how it should be used.',
        )
        .waitForElementVisible('#trigger-a-deprecation')
        .click('#trigger-a-deprecation')
        .assert.deprecationErrorExists(
          'The touchevents property of Modernizr has been deprecated in drupal:9.4.0 and is removed from drupal:10.0.0. There will be no replacement for this feature. See https://www.drupal.org/node/3277381.',
        )
        .drupalLogAndEnd({ onlyOnError: false });
    });
  },
};
