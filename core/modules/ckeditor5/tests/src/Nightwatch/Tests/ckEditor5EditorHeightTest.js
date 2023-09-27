module.exports = {
  '@tags': ['core', 'ckeditor5'],
  before(browser) {
    browser
      .drupalInstall({ installProfile: 'minimal' })
      .drupalInstallModule('ckeditor5', true)
      .drupalInstallModule('field_ui');

    // Set fixed (desktop-ish) size to ensure a maximum viewport.
    browser.resizeWindow(1920, 1080);
  },
  after(browser) {
    browser.drupalUninstall();
  },
  'Ensure CKEditor respects field widget row value': (browser) => {
    browser.drupalLoginAsAdmin(() => {
      browser
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
        .submitForm('input[type="submit"]')
        .waitForElementVisible('[data-drupal-messages]')
        .assert.textContains('[data-drupal-messages]', 'Added text format')
        // Create new content type.
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
        // Navigate to the create content page and measure height of the editor.
        .drupalRelativeURL('/node/add/test')
        .waitForElementVisible('.ck-editor__editable')
        .execute(
          // eslint-disable-next-line func-names, prefer-arrow-callback, no-shadow
          function () {
            const height = document.querySelector(
              '.ck-editor__editable',
            ).clientHeight;

            // We expect height to be 320, but test to ensure that it's greater
            // than 300. We want to ensure that we don't hard code a very specific
            // value because tests might break if styles change (line-height, etc).
            // Note that the default height for CKEditor5 is 47px.
            return height > 300;
          },
          [],
          (result) => {
            browser.assert.ok(
              result.value,
              'Editor height is set to 9 rows (default).',
            );
          },
        )
        .click('.ck-source-editing-button')
        .waitForElementVisible('.ck-source-editing-area')
        .execute(
          // eslint-disable-next-line func-names, prefer-arrow-callback, no-shadow
          function () {
            const height = document.querySelector(
              '.ck-source-editing-area',
            ).clientHeight;

            // We expect height to be 320, but test to ensure that it's greater
            // than 300. We want to ensure that we don't hard code a very specific
            // value because tests might break if styles change (line-height, etc).
            // Note that the default height for CKEditor5 is 47px.
            return height > 300;
          },
          [],
          (result) => {
            browser.assert.ok(
              result.value,
              'Source editing height is set to 9 rows (default).',
            );
          },
        )

        // Navigate to the create content page and measure max-height of the editor.
        .drupalRelativeURL('/node/add/test')
        .execute(
          // eslint-disable-next-line func-names, prefer-arrow-callback, no-shadow
          function () {
            window.Drupal.CKEditor5Instances.forEach((instance) => {
              instance.setData('<p>Llamas are cute.</p>'.repeat(100));
            });

            const height = document.querySelector(
              '.ck-editor__editable',
            ).clientHeight;

            return height < window.innerHeight;
          },
          [],
          (result) => {
            browser.assert.ok(
              result.value,
              'Editor area should never exceed full viewport.',
            );
          },
        )
        // Source Editor textarea should have vertical scrollbar when needed.
        .click('.ck-source-editing-button')
        .waitForElementVisible('.ck-source-editing-area')
        .execute(
          // eslint-disable-next-line func-names, prefer-arrow-callback, no-shadow
          function () {
            function isScrollableY(element) {
              const style = window.getComputedStyle(element);

              if (
                element.scrollHeight > element.clientHeight &&
                style.overflow !== 'hidden' &&
                style['overflow-y'] !== 'hidden' &&
                style.overflow !== 'clip' &&
                style['overflow-y'] !== 'clip'
              ) {
                if (
                  element === document.scrollingElement ||
                  (style.overflow !== 'visible' &&
                    style['overflow-y'] !== 'visible')
                ) {
                  return true;
                }
              }

              return false;
            }

            return isScrollableY(
              document.querySelector('.ck-source-editing-area textarea'),
            );
          },
          [],
          (result) => {
            browser.assert.strictEqual(
              result.value,
              true,
              'Source Editor textarea should have vertical scrollbar when needed.',
            );
          },
        )

        // Double the editor row count.
        .drupalRelativeURL('/admin/structure/types/manage/test/form-display')
        .waitForElementVisible(
          '[data-drupal-selector="edit-fields-body-settings-edit"]',
        )
        .click('[data-drupal-selector="edit-fields-body-settings-edit"]')
        .waitForElementVisible(
          '[data-drupal-selector="edit-fields-body-settings-edit-form-settings-rows"]',
        )
        .updateValue(
          '[data-drupal-selector="edit-fields-body-settings-edit-form-settings-rows"]',
          '18',
        )
        // Save field settings.
        .click(
          '[data-drupal-selector="edit-fields-body-settings-edit-form-actions-save-settings"]',
        )
        .waitForElementVisible(
          '[data-drupal-selector="edit-fields-body"] .field-plugin-summary',
        )
        .click('[data-drupal-selector="edit-submit"]')
        .waitForElementVisible('[data-drupal-messages]')
        .assert.textContains(
          '[data-drupal-messages]',
          'Your settings have been saved',
        )

        // Navigate to the create content page and measure height of the editor.
        .drupalRelativeURL('/node/add/test')
        .execute(
          // eslint-disable-next-line func-names, prefer-arrow-callback, no-shadow
          function () {
            const height = document.querySelector(
              '.ck-editor__editable',
            ).clientHeight;

            // We expect height to be 640, but test to ensure that it's greater
            // than 600. We want to ensure that we don't hard code a very specific
            // value because tests might break if styles change (line-height, etc).
            // Note that the default height for CKEditor5 is 47px.
            return height > 600;
          },
          [],
          (result) => {
            browser.assert.ok(result.value, 'Editor height is set to 18 rows.');
          },
        )
        .click('.ck-source-editing-button')
        .waitForElementVisible('.ck-source-editing-area')
        .execute(
          // eslint-disable-next-line func-names, prefer-arrow-callback, no-shadow
          function () {
            const height = document.querySelector(
              '.ck-source-editing-area',
            ).clientHeight;

            // We expect height to be 640, but test to ensure that it's greater
            // than 600. We want to ensure that we don't hard code a very specific
            // value because tests might break if styles change (line-height, etc).
            // Note that the default height for CKEditor5 is 47px.
            return height > 600;
          },
          [],
          (result) => {
            browser.assert.ok(
              result.value,
              'Source editing height is set to 18 rows (default).',
            );
          },
        );
    });
  },
};
