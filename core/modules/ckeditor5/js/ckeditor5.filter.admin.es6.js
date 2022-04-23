/**
 * @file
 * Provides Text Editor UI improvements specific to CKEditor 5.
 */
((Drupal, once) => {
  Drupal.behaviors.allowedTagsListener = {
    attach: function attach(context) {
      once(
        'ajax-conflict-prevention',
        '[data-drupal-selector="filter-format-edit-form"], [data-drupal-selector="filter-format-add-form"]',
        context,
      ).forEach((form) => {
        // When the form is submitted, remove the disabled attribute from all
        // AJAX enabled form elements. The disabled state is added as part of
        // AJAX processing, but will prevent the value from being added to
        // $form_state.
        form.addEventListener('submit', () => {
          once
            .filter(
              'drupal-ajax',
              '[data-drupal-selector="filter-format-edit-form"] [disabled], [data-drupal-selector="filter-format-add-form"] [disabled]',
            )
            // eslint-disable-next-line max-nested-callbacks
            .forEach((disabledElement) => {
              disabledElement.removeAttribute('disabled');
            });
        });
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
