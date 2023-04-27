module.exports = {
  '@tags': ['core', 'ckeditor5'],
  before(browser) {
    browser.drupalInstall({ installProfile: 'minimal' });
  },
  after(browser) {
    browser.drupalUninstall();
  },
  'Verify code block configured languages are respected': (browser) => {
    browser.drupalLoginAsAdmin(() => {
      browser
        // Enable required modules.
        .drupalRelativeURL('/admin/modules')
        .click('[name="modules[ckeditor5][enable]"]')
        .click('[name="modules[field_ui][enable]"]')
        .submitForm('input[type="submit"]') // Submit module form.
        .waitForElementVisible(
          '.system-modules-confirm-form input[value="Continue"]',
        )
        .submitForm('input[value="Continue"]') // Confirm installation of dependencies.
        .waitForElementVisible('.system-modules', 10000)

        // Create new input format.
        .drupalRelativeURL('/admin/config/content/formats/add')
        .waitForElementVisible('[data-drupal-selector="edit-name"]')
        .updateValue('[data-drupal-selector="edit-name"]', 'test')
        .waitForElementVisible('#edit-name-machine-name-suffix')
        .click(
          '[data-drupal-selector="edit-editor-editor"] option[value=ckeditor5]',
        )
        // Wait for CKEditor 5 settings to be visible.
        .waitForElementVisible(
          '[data-drupal-selector="edit-editor-settings-toolbar"]',
        )
        .click('.ckeditor5-toolbar-button-sourceEditing') // Select the Source Editing button.
        .keys(browser.Keys.DOWN) // Hit the down arrow key to move it to the toolbar.
        // Wait for new source editing vertical tab to be present before continuing.
        .waitForElementVisible(
          '[href*=edit-editor-settings-plugins-ckeditor5-sourceediting]',
        )
        .click('.ckeditor5-toolbar-item-codeBlock') // Select the Code Block button.
        .keys(browser.Keys.DOWN) // Hit the down arrow key to move it to the toolbar.
        // Wait for new code editing vertical tab to be present before continuing.
        .waitForElementVisible(
          '[href*=edit-editor-settings-plugins-ckeditor5-codeblock]',
        )
        .click('[href*=edit-editor-settings-plugins-ckeditor5-codeblock]')
        .setValue(
          '[data-drupal-selector="edit-editor-settings-plugins-ckeditor5-codeblock-languages"]',
          'twig|Twig\nyml|YML',
        )
        .submitForm('input[type="submit"]')
        .waitForElementVisible('[data-drupal-messages]')
        .assert.textContains('[data-drupal-messages]', 'Added text format')

        // Create a new content type.
        .drupalRelativeURL('/admin/structure/types/add')
        .waitForElementVisible('[data-drupal-selector="edit-name"]')
        .updateValue('[data-drupal-selector="edit-name"]', 'test')
        .waitForElementVisible('#edit-name-machine-name-suffix') // Wait for machine name to update.
        .submitForm('input[type="submit"]')
        .waitForElementVisible('[data-drupal-messages]')
        .assert.textContains(
          '[data-drupal-messages]',
          'The content type test has been added',
        )

        // Navigate to create new content.
        .drupalRelativeURL('/node/add/test')
        .waitForElementVisible('.ck-editor__editable')

        // Open code block dropdown, and verify that correct languages are present.
        .click(
          '.ck-code-block-dropdown .ck-dropdown__button .ck-splitbutton__arrow',
        )
        .assert.containsText(
          '.ck-code-block-dropdown .ck-dropdown__panel .ck-list__item:nth-child(1) .ck-button__label',
          'Twig',
        )
        .assert.containsText(
          '.ck-code-block-dropdown .ck-dropdown__panel .ck-list__item:nth-child(2) .ck-button__label',
          'YML',
        )

        // Click the first language (which should be 'Twig').
        .click(
          '.ck-code-block-dropdown .ck-dropdown__panel .ck-list__item:nth-child(1) button',
        )
        .waitForElementVisible('.ck-editor__main pre[data-language="Twig"]')
        .keys('x') // Press 'X' to ensure there's data in CKEditor before switching to source view.
        .pause(50)

        // Go into source editing and verify that correct CSS class is added.
        .click('.ck-source-editing-button')
        .waitForElementVisible('.ck-source-editing-area')
        .assert.valueContains(
          '.ck-source-editing-area textarea',
          '<pre><code class="language-twig">',
        )

        // Go back into WYSIWYG mode and hit enter three times to break out of code block.
        .click('.ck-source-editing-button') // Disable source editing.
        .waitForElementVisible('.ck-editor__editable:not(.ck-hidden)')
        .keys(browser.Keys.RIGHT) // Go to end of line.
        .pause(50)

        // Hit Enter three times to break out of CKEditor's code block.
        .keys(browser.Keys.ENTER)
        .pause(50)
        .keys(browser.Keys.ENTER)
        .pause(50)
        .keys(browser.Keys.ENTER)
        .pause(50)

        // Open up the code syntax dropdown, and click the 2nd item (which should be 'YML').
        .click(
          '.ck-code-block-dropdown .ck-dropdown__button .ck-splitbutton__arrow',
        )
        .click(
          '.ck-code-block-dropdown .ck-dropdown__panel .ck-list__item:nth-child(2) button',
        )
        .keys('x') // Press 'X' to ensure there's data in CKEditor before switching to source view.

        // Go into source editing and verify that correct CSS class is added.
        .click('.ck-source-editing-button')
        .waitForElementVisible('.ck-source-editing-area')
        .assert.valueContains(
          '.ck-source-editing-area textarea',
          '<pre><code class="language-yml">',
        );
    });
  },
};
