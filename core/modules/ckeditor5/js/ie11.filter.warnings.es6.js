/**
 * @file
 * Provides IE11 compatibility warnings when choosing a text editor.
 */

((Drupal, once, Modernizr) => {
  /**
   * Presents a warning when selecting CKEditor 5 as a text format's text editor.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.ckEditor5warn = {
    attach: function attach() {
      const isIE11 = Modernizr.mq(
        '(-ms-high-contrast: active), (-ms-high-contrast: none)',
      );
      const editorSelect = once(
        'editor-select',
        document.querySelector(
          '#filter-format-edit-form #edit-editor-editor, #filter-format-add-form #edit-editor-editor',
        ),
      );

      if (typeof editorSelect[0] !== 'undefined') {
        const select = editorSelect[0];

        // Add a container for messages above the text format select element.
        const selectMessageContainer = document.createElement('div');
        select.parentNode.insertBefore(selectMessageContainer, select);
        const selectMessages = new Drupal.Message(selectMessageContainer);
        const editorSettings = document.querySelector(
          '#editor-settings-wrapper',
        );

        /**
         * Adds an IE11 compatibility warning to the message container.
         */
        const ck5Warning = () => {
          selectMessages.add(
            Drupal.t(
              'CKEditor 5 is not compatible with Internet Explorer. Text fields using CKEditor 5 will fall back to plain HTML editing without CKEditor for users of Internet Explorer.',
            ),
            {
              type: 'warning',
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
              },
            );
            editorSettings.hidden = true;
          }
        };

        /**
         * Adds a warning if the selected editor is ckeditor5, otherwise clears
         * the message container.
         */
        const updateWarningStatus = () => {
          if (
            select.value === 'ckeditor5' &&
            !select.classList.contains('error')
          ) {
            ck5Warning();
          } else {
            editorSettings.hidden = false;
            selectMessages.clear();
          }
        };

        const selectChangeHandler = () => {
          // Declare the observer first so the observer callback can access it.
          let editorSelectObserver = null;

          /**
           * MutationObserver callback for the editor select.
           *
           * This listens for the removal 'disabled' attribute on the <select>,
           * which means the AJAX callback has completed and the form is in a
           * state suitable for seeing if the IE11 warning is needed.
           *
           * @param {Array} mutations
           *   The element's mutations.
           */
          function whenSelectAttributeChanges(mutations) {
            for (let i = 0; i < mutations.length; i++) {
              // When the select input is no longer disabled, the AJAX request
              // is complete and the UI is in a state where it can be determined
              // if the IE11 warning is needed.
              if (
                mutations[i].type === 'attributes' &&
                mutations[i].attributeName === 'disabled' &&
                !select.disabled
              ) {
                updateWarningStatus();
                editorSelectObserver.disconnect();
              }
            }
          }

          // An observer is used because during the select change event, it is
          // not yet known if validation prevented the switch to CKEditor 5.
          // The IE11 warning should only appear if the switch wasn't prevented
          // by validation.
          editorSelectObserver = new MutationObserver(
            whenSelectAttributeChanges,
          );
          editorSelectObserver.observe(select, {
            attributes: true,
            attributeOldValue: true,
          });
        };

        updateWarningStatus();

        // Listen to text format selection changes.
        select.addEventListener('change', selectChangeHandler);
      }
    },
  };
})(Drupal, once, Modernizr);
