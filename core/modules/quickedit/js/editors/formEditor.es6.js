/**
 * @file
 * Form-based in-place editor. Works for any field type.
 */

(function ($, Drupal, _) {
  /**
   * @constructor
   *
   * @augments Drupal.quickedit.EditorView
   */
  Drupal.quickedit.editors.form = Drupal.quickedit.EditorView.extend(
    /** @lends Drupal.quickedit.editors.form# */ {
      /**
       * Tracks form container DOM element that is used while in-place editing.
       *
       * @type {jQuery}
       */
      $formContainer: null,

      /**
       * Holds the {@link Drupal.Ajax} object.
       *
       * @type {Drupal.Ajax}
       */
      formSaveAjax: null,

      /**
       * {@inheritdoc}
       *
       * @param {object} fieldModel
       *   The field model that holds the state.
       * @param {string} state
       *   The state to change to.
       */
      stateChange(fieldModel, state) {
        const from = fieldModel.previous('state');
        const to = state;
        switch (to) {
          case 'inactive':
            break;

          case 'candidate':
            if (from !== 'inactive') {
              this.removeForm();
            }
            break;

          case 'highlighted':
            break;

          case 'activating':
            // If coming from an invalid state, then the form is already loaded.
            if (from !== 'invalid') {
              this.loadForm();
            }
            break;

          case 'active':
            break;

          case 'changed':
            break;

          case 'saving':
            this.save();
            break;

          case 'saved':
            break;

          case 'invalid':
            this.showValidationErrors();
            break;
        }
      },

      /**
       * {@inheritdoc}
       *
       * @return {object}
       *   A settings object for the quick edit UI.
       */
      getQuickEditUISettings() {
        return {
          padding: true,
          unifiedToolbar: true,
          fullWidthToolbar: true,
          popup: true,
        };
      },

      /**
       * Loads the form for this field, displays it on top of the actual field.
       */
      loadForm() {
        const fieldModel = this.fieldModel;

        // Generate a DOM-compatible ID for the form container DOM element.
        const id = `quickedit-form-for-${fieldModel.id.replace(
          /[/[\]]/g,
          '_',
        )}`;

        // Render form container.
        const $formContainer = $(
          Drupal.theme('quickeditFormContainer', {
            id,
            loadingMsg: Drupal.t('Loading…'),
          }),
        );
        this.$formContainer = $formContainer;
        $formContainer
          .find('.quickedit-form')
          .addClass(
            'quickedit-editable quickedit-highlighted quickedit-editing',
          )
          .attr('role', 'dialog');

        // Insert form container in DOM.
        if (this.$el.css('display') === 'inline') {
          $formContainer.prependTo(this.$el.offsetParent());
          // Position the form container to render on top of the field's element.
          const pos = this.$el.position();
          $formContainer.css('left', pos.left).css('top', pos.top);
        } else {
          $formContainer.insertBefore(this.$el);
        }

        // Load form, insert it into the form container and attach event handlers.
        const formOptions = {
          fieldID: fieldModel.get('fieldID'),
          $el: this.$el,
          nocssjs: false,
          // Reset an existing entry for this entity in the PrivateTempStore (if
          // any) when loading the field. Logically speaking, this should happen
          // in a separate request because this is an entity-level operation, not
          // a field-level operation. But that would require an additional
          // request, that might not even be necessary: it is only when a user
          // loads a first changed field for an entity that this needs to happen:
          // precisely now!
          reset: !fieldModel.get('entity').get('inTempStore'),
        };
        Drupal.quickedit.util.form.load(formOptions, (form, ajax) => {
          Drupal.AjaxCommands.prototype.insert(ajax, {
            data: form,
            selector: `#${id} .placeholder`,
          });

          $formContainer
            .on('formUpdated.quickedit', ':input', (event) => {
              const state = fieldModel.get('state');
              // If the form is in an invalid state, it will persist on the page.
              // Set the field to activating so that the user can correct the
              // invalid value.
              if (state === 'invalid') {
                fieldModel.set('state', 'activating');
              }
              // Otherwise assume that the fieldModel is in a candidate state and
              // set it to changed on formUpdate.
              else {
                fieldModel.set('state', 'changed');
              }
            })
            .on('keypress.quickedit', 'input', (event) => {
              if (event.keyCode === 13) {
                return false;
              }
            });

          // The in-place editor has loaded; change state to 'active'.
          fieldModel.set('state', 'active');
        });
      },

      /**
       * Removes the form for this field, detaches behaviors and event handlers.
       */
      removeForm() {
        if (this.$formContainer === null) {
          return;
        }

        delete this.formSaveAjax;
        // Allow form widgets to detach properly.
        Drupal.detachBehaviors(this.$formContainer.get(0), null, 'unload');
        this.$formContainer
          .off('change.quickedit', ':input')
          .off('keypress.quickedit', 'input')
          .remove();
        this.$formContainer = null;
      },

      /**
       * {@inheritdoc}
       */
      save() {
        const $formContainer = this.$formContainer;
        const $submit = $formContainer.find('.quickedit-form-submit');
        const editorModel = this.model;
        const fieldModel = this.fieldModel;

        // Create an AJAX object for the form associated with the field.
        let formSaveAjax = Drupal.quickedit.util.form.ajaxifySaving(
          {
            nocssjs: false,
            other_view_modes: fieldModel.findOtherViewModes(),
          },
          $submit,
        );

        function cleanUpAjax() {
          Drupal.quickedit.util.form.unajaxifySaving(formSaveAjax);
          formSaveAjax = null;
        }

        // Successfully saved.
        formSaveAjax.commands.quickeditFieldFormSaved = function (
          ajax,
          response,
          status,
        ) {
          cleanUpAjax();
          // First, transition the state to 'saved'.
          fieldModel.set('state', 'saved');
          // Second, set the 'htmlForOtherViewModes' attribute, so that when this
          // field is rerendered, the change can be propagated to other instances
          // of this field, which may be displayed in different view modes.
          fieldModel.set('htmlForOtherViewModes', response.other_view_modes);
          // Finally, set the 'html' attribute on the field model. This will cause
          // the field to be rerendered.
          _.defer(() => {
            fieldModel.set('html', response.data);
          });
        };

        // Unsuccessfully saved; validation errors.
        formSaveAjax.commands.quickeditFieldFormValidationErrors = function (
          ajax,
          response,
          status,
        ) {
          editorModel.set('validationErrors', response.data);
          fieldModel.set('state', 'invalid');
        };

        // The quickeditFieldForm AJAX command is called upon attempting to save
        // the form; Form API will mark which form items have errors, if any. This
        // command is invoked only if validation errors exist and then it runs
        // before editFieldFormValidationErrors().
        formSaveAjax.commands.quickeditFieldForm = function (
          ajax,
          response,
          status,
        ) {
          Drupal.AjaxCommands.prototype.insert(ajax, {
            data: response.data,
            selector: `#${$formContainer.attr('id')} form`,
          });
        };

        // Click the form's submit button; the scoped AJAX commands above will
        // handle the server's response.
        $submit.trigger('click.quickedit');
      },

      /**
       * {@inheritdoc}
       */
      showValidationErrors() {
        this.$formContainer
          .find('.quickedit-form')
          .addClass('quickedit-validation-error')
          .find('form')
          .prepend(this.model.get('validationErrors'));
      },
    },
  );
})(jQuery, Drupal, _);
