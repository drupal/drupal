/**
 * @file
 * Form-based in-place editor. Works for any field type.
 */

(function ($, Drupal) {

  "use strict";

  Drupal.edit.editors.form = Drupal.edit.EditorView.extend({

    // Tracks the form container DOM element that is used while in-place editing.
    $formContainer: null,

    // Holds the Drupal.ajax object
    formSaveAjax: null,

    /**
     * {@inheritdoc}
     */
    stateChange: function (fieldModel, state) {
      var from = fieldModel.previous('state');
      var to = state;
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
     */
    getEditUISettings: function () {
      return { padding: true, unifiedToolbar: true, fullWidthToolbar: true, popup: true };
    },

    /**
     * Loads the form for this field, displays it on top of the actual field.
     */
    loadForm: function () {
      var fieldModel = this.fieldModel;

      // Generate a DOM-compatible ID for the form container DOM element.
      var id = 'edit-form-for-' + fieldModel.id.replace(/[\/\[\]]/g, '_');

      // Render form container.
      var $formContainer = this.$formContainer = $(Drupal.theme('editFormContainer', {
        id: id,
        loadingMsg: Drupal.t('Loading…')
      }
      ));
      $formContainer
        .find('.edit-form')
        .addClass('edit-editable edit-highlighted edit-editing')
        .attr('role', 'dialog');

      // Insert form container in DOM.
      if (this.$el.css('display') === 'inline') {
        $formContainer.prependTo(this.$el.offsetParent());
        // Position the form container to render on top of the field's element.
        var pos = this.$el.position();
        $formContainer.css('left', pos.left).css('top', pos.top);
      }
      else {
        $formContainer.insertBefore(this.$el);
      }

      // Load form, insert it into the form container and attach event handlers.
      var formOptions = {
        fieldID: fieldModel.get('fieldID'),
        $el: this.$el,
        nocssjs: false,
        // Reset an existing entry for this entity in the TempStore (if any) when
        // loading the field. Logically speaking, this should happen in a separate
        // request because this is an entity-level operation, not a field-level
        // operation. But that would require an additional request, that might not
        // even be necessary: it is only when a user loads a first changed field
        // for an entity that this needs to happen: precisely now!
        reset: !fieldModel.get('entity').get('inTempStore')
      };
      Drupal.edit.util.form.load(formOptions, function (form, ajax) {
        Drupal.AjaxCommands.prototype.insert(ajax, {
          data: form,
          selector: '#' + id + ' .placeholder'
        });

        $formContainer
          .on('formUpdated.edit', ':input', function (event) {
            var state = fieldModel.get('state');
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
          .on('keypress.edit', 'input', function (event) {
            if (event.keyCode === 13) {
              return false;
            }
          });

        // The in-place editor has loaded; change state to 'active'.
        fieldModel.set('state', 'active');
      });
    },

    /**
     * Removes the form for this field and detaches behaviors and event handlers.
     */
    removeForm: function () {
      if (this.$formContainer === null) {
        return;
      }

      delete this.formSaveAjax;
      // Allow form widgets to detach properly.
      Drupal.detachBehaviors(this.$formContainer.get(0), null, 'unload');
      this.$formContainer
        .off('change.edit', ':input')
        .off('keypress.edit', 'input')
        .remove();
      this.$formContainer = null;
    },

    /**
     * {@inheritdoc}
     */
    save: function () {
      var $formContainer = this.$formContainer;
      var $submit = $formContainer.find('.edit-form-submit');
      var editorModel = this.model;
      var fieldModel = this.fieldModel;

      function cleanUpAjax() {
        Drupal.edit.util.form.unajaxifySaving(formSaveAjax);
        formSaveAjax = null;
      }

      // Create an AJAX object for the form associated with the field.
      var formSaveAjax = Drupal.edit.util.form.ajaxifySaving({
        nocssjs: false,
        other_view_modes: fieldModel.findOtherViewModes()
      }, $submit);

      // Successfully saved.
      formSaveAjax.commands.editFieldFormSaved = function (ajax, response, status) {
        cleanUpAjax();
        // First, transition the state to 'saved'.
        fieldModel.set('state', 'saved');
        // Second, set the 'htmlForOtherViewModes' attribute, so that when this
        // field is rerendered, the change can be propagated to other instances of
        // this field, which may be displayed in different view modes.
        fieldModel.set('htmlForOtherViewModes', response.other_view_modes);
        // Finally, set the 'html' attribute on the field model. This will cause
        // the field to be rerendered.
        _.defer(function () {
          fieldModel.set('html', response.data);
        });
      };

      // Unsuccessfully saved; validation errors.
      formSaveAjax.commands.editFieldFormValidationErrors = function (ajax, response, status) {
        editorModel.set('validationErrors', response.data);
        fieldModel.set('state', 'invalid');
      };

      // The edit_field_form AJAX command is called upon attempting to save
      // the form; Form API will mark which form items have errors, if any. This
      // command is invoked only if validation errors exist and then it runs
      // before editFieldFormValidationErrors().
      formSaveAjax.commands.editFieldForm = function (ajax, response, status) {
        Drupal.AjaxCommands.prototype.insert(ajax, {
          data: response.data,
          selector: '#' + $formContainer.attr('id') + ' form'
        });
      };

      // Click the form's submit button; the scoped AJAX commands above will
      // handle the server's response.
      $submit.trigger('click.edit');
    },

    /**
     * {@inheritdoc}
     */
    showValidationErrors: function () {
      this.$formContainer
        .find('.edit-form')
        .addClass('edit-validation-error')
        .find('form')
        .prepend(this.model.get('validationErrors'));
    }
  });

})(jQuery, Drupal);
