/**
 * @file
 * Provides IE11 compatibility warnings when choosing a text editor.
 */

((Drupal, once) => {
  /**
   * Presents a warning when selecting CKEditor 5 as a text format's text editor.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.ckEditor5warn = {
    attach: function attach() {
      const isIE11 = !!document.documentMode;
      const editorSelect = once(
        'editor-ie11-warning',
        '[data-drupal-selector="filter-format-edit-form"] [data-drupal-selector="edit-editor-editor"], [data-drupal-selector="filter-format-add-form"] [data-drupal-selector="edit-editor-editor"]',
      );

      if (typeof editorSelect[0] !== 'undefined') {
        const select = editorSelect[0];

        // Add a container for messages above the text format select element.
        const selectMessageContainer = document.createElement('div');
        select.parentNode.after(selectMessageContainer, select);
        const selectMessages = new Drupal.Message(selectMessageContainer);
        const editorSettings = document.querySelector(
          '#editor-settings-wrapper',
        );

        /**
         * Adds IE11 compatibility warnings to the message container.
         */
        const addIE11Warning = () => {
          selectMessages.add(
            Drupal.t(
              'CKEditor 5 is not compatible with Internet Explorer. Text fields using CKEditor 5 will fall back to plain HTML editing without CKEditor for users of Internet Explorer.',
            ),
            {
              type: 'warning',
              id: 'ie_11_warning',
            },
          );
          if (isIE11) {
            // https://www.drupal.org/docs/system-requirements/browser-requirements
            selectMessages.add(
              Drupal.t(
                'Text editor toolbar settings are not available in Internet Explorer. They will be available in other <a href="@supported-browsers">supported browsers</a>.',
                {
                  '@supported-browsers':
                    'https://www.drupal.org/docs/system-requirements/browser-requirements',
                },
              ),
              {
                type: 'error',
                id: 'ie_11_error',
              },
            );
            editorSettings.hidden = true;
          }
        };

        /**
         * Adds a warning if the selected editor is CKEditor 5, otherwise clears
         * any existing IE11 warnings.
         */
        const updateWarningStatus = () => {
          if (
            select.value === 'ckeditor5' &&
            !select.hasAttribute('data-error-switching-to-ckeditor5')
          ) {
            addIE11Warning();
          } else {
            if (selectMessages.select('ie_11_warning')) {
              selectMessages.remove('ie_11_warning');
            }
            if (selectMessages.select('ie_11_error')) {
              selectMessages.remove('ie_11_error');
            }
          }
        };

        updateWarningStatus();

        // This observer listens for two different attribute changes that, when
        // they occur, may require adding or removing the IE11 warnings.
        // - If the disabled attribute was removed, which is potentially due to
        //   an AJAX update having completed.
        // - If the data-error-switching-to-ckeditor5 attribute was removed,
        //   which means a switch to CKEditor 5 that was previously blocked due
        //   to validation errors has resumed and completed.
        const editorSelectObserver = new MutationObserver((mutations) => {
          for (let i = 0; i < mutations.length; i++) {
            // When the select input is no longer disabled, the AJAX request
            // is complete and the UI is in a state where it can be determined
            // if the ckeditor_stylesheets warning is needed.
            const switchToCKEditor5Complete =
              mutations[i].type === 'attributes' &&
              mutations[i].attributeName === 'disabled' &&
              !select.disabled;
            const fixedErrorsPreventingSwitchToCKEditor5 =
              mutations[i].type === 'attributes' &&
              mutations[i].attributeName ===
                'data-error-switching-to-ckeditor5' &&
              !select.hasAttribute('data-error-switching-to-ckeditor5');
            if (
              switchToCKEditor5Complete ||
              fixedErrorsPreventingSwitchToCKEditor5
            ) {
              updateWarningStatus();
            }
          }
        });

        editorSelectObserver.observe(select, {
          attributes: true,
        });
      }
    },
  };
})(Drupal, once);
