const mainContent = '#block-olivero-content';
const mainMessagesContainer = '[data-drupal-messages] > .messages__wrapper';
const secondaryMessagesContainer = '[data-drupal-messages-other]';

const mainButtons = {
  addStatus: '#add--status',
  removeStatus: '#remove--status',
  addError: '#add--error',
  removeError: '#remove--error',
  addWarning: '#add--warning',
  removeWarning: '#remove--warning',
  clearAll: '#clear-all',
};

const secondaryButtons = {
  addStatus: '[id="add-[data-drupal-messages-other]-status"]',
  removeStatus: '[id="remove-[data-drupal-messages-other]-status"]',
  addError: '[id="add-[data-drupal-messages-other]-error"]',
  removeError: '[id="remove-[data-drupal-messages-other]-error"]',
  addWarning: '[id="add-[data-drupal-messages-other]-warning"]',
  removeWarning: '[id="remove-[data-drupal-messages-other]-warning"]',
};

module.exports = {
  '@tags': ['core', 'olivero'],
  before(browser) {
    browser.drupalInstall({
      setupFile:
        'core/tests/Drupal/TestSite/TestSiteOliveroInstallTestScript.php',
      installProfile: 'minimal',
    });
  },
  after(browser) {
    browser.drupalUninstall();
  },
  'Verify default placement of javascript-created messages': (browser) => {
    browser
      .drupalRelativeURL('/js_message_test_link_with_system_messages')
      .waitForElementVisible(mainContent)
      .assert.elementPresent(mainMessagesContainer)

      // We should load 3 messages on page load from \Drupal::messenger()
      .assert.elementCount(`${mainMessagesContainer} > .messages-list__item`, 3)

      // We should have one message of each type
      .assert.elementCount(`${mainMessagesContainer} > .messages--status`, 1)
      .assert.elementCount(`${mainMessagesContainer} > .messages--warning`, 1)
      .assert.elementCount(`${mainMessagesContainer} > .messages--error`, 1)

      // Trigger new messages via javascript
      .click(mainButtons.addStatus)
      .click(mainButtons.addWarning)
      .click(mainButtons.addError)

      // We should have 6 total messages
      .assert.elementCount(`${mainMessagesContainer} > .messages-list__item`, 6)

      // We should have 2 messages of each type
      .assert.elementCount(`${mainMessagesContainer} > .messages--status`, 2)
      .assert.elementCount(`${mainMessagesContainer} > .messages--warning`, 2)
      .assert.elementCount(`${mainMessagesContainer} > .messages--error`, 2);
  },

  'Verify customized placement of javascript-created messages': (browser) => {
    browser
      .drupalRelativeURL('/js_message_test_link_with_system_messages')
      .waitForElementVisible(mainContent)
      .assert.elementPresent(secondaryMessagesContainer)

      // We should load 3 messages on page load from \Drupal::messenger()
      .assert.elementCount(
        `${secondaryMessagesContainer} > .messages-list__item`,
        0,
      )

      // Trigger new messages via javascript
      .click(secondaryButtons.addStatus)
      .click(secondaryButtons.addWarning)
      .click(secondaryButtons.addError)

      // We should have 6 total messages
      .assert.elementCount(
        `${secondaryMessagesContainer} > .messages-list__item`,
        3,
      )

      // We should have 2 messages of each type
      .assert.elementCount(
        `${secondaryMessagesContainer} > .messages--status`,
        1,
      )
      .assert.elementCount(
        `${secondaryMessagesContainer} > .messages--warning`,
        1,
      )
      .assert.elementCount(
        `${secondaryMessagesContainer} > .messages--error`,
        1,
      );
  },
};
