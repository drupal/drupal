/**
 * @file
 * Provides Text Editor UI improvements specific to CKEditor 5.
 */

((Drupal, once) => {
  Drupal.behaviors.allowedTagsListener = {
    attach: function attach(context) {
      once(
        'allowed-tags-listener',
        context.querySelector(
          '[data-drupal-selector="edit-filters-filter-html-settings-allowed-html"]',
        ),
      ).forEach((textarea) => {
        const editorSelect = document.querySelector(
          '[data-drupal-selector="edit-editor-editor"]',
        );
        const filterCheckbox = document.querySelector(
          '[data-drupal-selector="edit-filters-filter-html-status"]',
        );
        const formSubmit = document.querySelector(
          '[data-drupal-selector="edit-actions-submit"]',
        );
        const wrapper = textarea.closest('div');

        const resetChanges = () => {
          const updateButtonContainer = document.querySelector(
            '[data-ckeditor5-allowed-tags-info]',
          );
          if (updateButtonContainer) {
            updateButtonContainer.remove();
          }

          const allowedTagsDisabledHelp = document.querySelector(
            '[data-ckeditor5-allowed-tags-disabled-help]',
          );
          if (allowedTagsDisabledHelp) {
            allowedTagsDisabledHelp.remove();
          }

          formSubmit.removeAttribute('disabled');
          wrapper.classList.remove('ckeditor5-filter-attention');
        };

        resetChanges();

        const addAllowedTagsUpdateButton = () => {
          resetChanges();

          if (
            editorSelect.value === 'ckeditor5' &&
            filterCheckbox &&
            filterCheckbox.checked
          ) {
            if (!textarea.hasAttribute('readonly')) {
              wrapper.classList.add('ckeditor5-filter-attention');

              const container = document.createElement('div');
              container.setAttribute('data-ckeditor5-allowed-tags-info', true);
              const description = document.createElement('p');

              description.innerText = Drupal.t(
                'Switching to CKEditor 5 requires, at minimum, the tags "<p> <br>". After switching to CKEditor 5, this field will be read only, and will be updated based on which CKEditor 5 plugins are enabled. When switching to CKEditor 5 from an existing text format with content, we recommend documenting what tags are in use and then enabling the CKEditor 5 tags that support them.',
              );

              const updateButton = document.createElement('button');
              updateButton.setAttribute(
                'name',
                'update-ckeditor5-allowed-tags',
              );
              updateButton.innerText = Drupal.t(
                'Apply changes to allowed tags.',
              );
              updateButton.addEventListener('click', () => {
                editorSelect.dispatchEvent(new CustomEvent('change'));
                setTimeout(() => {
                  resetChanges();
                });
              });

              container.appendChild(description);
              container.appendChild(updateButton);

              wrapper.appendChild(container);

              // In this very specific use case, submitting the filter form must
              // be prevented.
              // - CKEditor 5 is the selected editor, but it's not yet accepted
              //   by the form because it's not passing the validation restraint
              //   requiring CKEditor 5 compatible "Allowed Tags". This validator,
              //   by necessity fires before CKEditor 5 is registered with the
              //   form.
              // - The registering of an editor with the form typically occurs
              //   when a change
              formSubmit.setAttribute('disabled', true);
              const formSubmitHelp = document.createElement('p');
              formSubmitHelp.setAttribute(
                'data-ckeditor5-allowed-tags-disabled-help',
                true,
              );
              formSubmitHelp.textContent = Drupal.t(
                'This form is not submittable when the editor is set to CKEditor 5 unless the "Limit allowed HTML tags and correct faulty HTML" filter\'s "Allowed HTML tags" field includes the tags required by CKEDitor 5',
              );
              formSubmit.parentNode.append(formSubmitHelp);
            }
          }
        };
        editorSelect.addEventListener('change', addAllowedTagsUpdateButton);
        filterCheckbox.addEventListener('change', addAllowedTagsUpdateButton);
      });
    },
  };

  // Copy the function that is about to be overridden so it can be invoked
  // inside the override.
  const originalAjaxEventResponse = Drupal.Ajax.prototype.eventResponse;

  /**
   * Overrides Ajax.eventResponse with CKEditor 5 specific customizations.
   *
   * This is the handler for events that will ultimately trigger an AJAX
   * response. It is overridden here to provide additional logic to prevent
   * specific CKEditor 5-related events from triggering that AJAX response
   * unless certain criteria are met.
   */
  Drupal.Ajax.prototype.eventResponse = function ckeditor5AjaxEventResponse(
    ...args
  ) {
    // There are AJAX callbacks that should only be triggered if the editor
    // <select> is set to 'ckeditor5'. They should be active when the text
    // format is using CKEditor 5 and when a user is attempting to switch to
    // CKEditor 5 but is prevented from doing so by validation. Triggering these
    // AJAX callback when trying to switch to CKEditor 5 but blocked by
    // validation benefits the user as they get real time feedback as they
    // configure the text format to be CKEditor 5 compatible. This spares them
    // from having to submit the form multiple times in order to determine if
    // their settings are compatible.
    // This validation stage is also why the AJAX callbacks can't be
    // conditionally added server side, as validation errors interrupt the form
    // rebuild before the AJAX callbacks could be added via form_alter.
    if (this.ckeditor5_only) {
      // The ckeditor5_only property is added to form elements that should only
      // trigger AJAX callbacks when the editor <select> value is 'ckeditor5'.
      // These callbacks provide real-time validation that should be present for
      // both text formats using CKEditor 5 and text formats in the process of
      // switching to CKEditor 5, but prevented from doing so by validation.
      if (
        this.$form[0].querySelector('#edit-editor-editor').value !== 'ckeditor5'
      ) {
        return;
      }
    }

    originalAjaxEventResponse.apply(this, args);
  };
})(Drupal, once);
