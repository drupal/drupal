// The javascript that creates dropbuttons is not present on the /page at
// initial load.  If the once data property is added then the JS was loaded
// and triggered on the inserted content.
// @see \Drupal\test_htmx\Controller\HtmxTestAttachmentsController
// @see core/modules/system/tests/modules/test_htmx/js/reveal-merged-settings.js

const scriptSelector = 'script[src*="test_htmx/js/behavior.js"]';
const cssSelector = 'link[rel="stylesheet"][href*="test_htmx/css/style.css"]';
const elementSelector = '.ajax-content';
const elementInitSelector = `${elementSelector}[data-once="htmx-init"]`;

module.exports = {
  '@tags': ['core', 'htmx'],
  before(browser) {
    browser.drupalInstall({
      setupFile: 'core/tests/Drupal/TestSite/HtmxAssetLoadTestSetup.php',
      installProfile: 'minimal',
    });
  },
  afterEach(browser) {
    browser.drupalLogAndEnd({ onlyOnError: true });
  },
  after(browser) {
    browser.drupalUninstall();
  },

  'Asset Load': (browser) => {
    // Load the route htmx will use for the request on click and confirm the
    // markup we will be looking for is present in the source markup.
    browser
      .drupalRelativeURL('/htmx-test-attachments/replace')
      .waitForElementVisible('body', 1000)
      .assert.elementPresent(elementInitSelector);
    // Now load the page with the htmx enhanced button and verify the absence
    // of the markup to be inserted. Click the button
    // and check for inserted javascript and markup.
    browser
      .drupalRelativeURL('/htmx-test-attachments/page')
      .waitForElementVisible('body', 1000)
      .assert.not.elementPresent(scriptSelector)
      .assert.not.elementPresent(cssSelector)
      .waitForElementVisible('[name="replace"]', 1000)
      .click('[name="replace"]')
      .waitForElementVisible(elementSelector, 6000)
      .waitForElementVisible(elementInitSelector, 6000)
      .assert.elementPresent(scriptSelector)
      .assert.elementPresent(cssSelector);
  },

  'Ajax Load HTMX Element': (browser) => {
    // Load the route htmx will use for the request on click and confirm the
    // markup we will be looking for is present in the source markup.
    browser
      .drupalRelativeURL('/htmx-test-attachments/replace')
      .waitForElementVisible('body', 1000)
      .assert.elementPresent(scriptSelector);
    // Now load the page with the ajax powered button. Click the button
    // to insert an htmx enhanced button and verify the absence
    // of the markup to be inserted. Click the button
    // and check for inserted javascript and markup.
    browser
      .drupalRelativeURL('/htmx-test-attachments/ajax')
      .waitForElementVisible('body', 1000)
      .assert.not.elementPresent(scriptSelector)
      .assert.not.elementPresent(cssSelector)
      .waitForElementVisible('[data-drupal-selector="edit-ajax-button"]', 1000)
      .pause(1000)
      .click('[data-drupal-selector="edit-ajax-button"]')
      .waitForElementVisible('[name="replace"]', 1000)
      .pause(1000)
      .click('[name="replace"]')
      .waitForElementVisible(elementSelector, 6000)
      .waitForElementVisible(elementInitSelector, 6000)
      .assert.elementPresent(scriptSelector)
      .assert.elementPresent(cssSelector);
  },
};
