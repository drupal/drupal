/**
 * @file
 * Form-based in-place editor. Works for any field type.
 */
(function ($, Drupal) {

"use strict";

Drupal.edit.editors.form = Drupal.edit.EditorView.extend({

  // Tracks the form container DOM element that is used while in-place editing.
  $formContainer: null,

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
        if (from === 'invalid') {
          // No need to call removeValidationErrors() for this in-place editor!
        }
        break;
      case 'highlighted':
        break;
      case 'activating':
        this.loadForm();
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
   * Loads the form for this field, displays it on top of the actual field.
   */
  loadForm: function () {
    var fieldModel = this.fieldModel;

    // Generate a DOM-compatible ID for the form container DOM element.
    var id = 'edit-form-for-' + fieldModel.id.replace(/\//g, '_');

    // Render form container.
    var $formContainer = this.$formContainer = $(Drupal.theme('editFormContainer', {
      id: id,
      loadingMsg: Drupal.t('Loading…')}
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
      fieldID: fieldModel.id,
      $el: this.$el,
      nocssjs: false
    };
    Drupal.edit.util.form.load(formOptions, function (form, ajax) {
      Drupal.AjaxCommands.prototype.insert(ajax, {
        data: form,
        selector: '#' + id + ' .placeholder'
      });

      var $submit = $formContainer.find('.edit-form-submit');
      Drupal.edit.util.form.ajaxifySaving(formOptions, $submit);
      $formContainer
        .on('formUpdated.edit', ':input', function () {
          fieldModel.set('state', 'changed');
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

    Drupal.edit.util.form.unajaxifySaving(this.$formContainer.find('.edit-form-submit'));
    // Allow form widgets to detach properly.
    Drupal.detachBehaviors(this.$formContainer, null, 'unload');
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
    var base = $submit.attr('id');
    var editorModel = this.model;
    var fieldModel = this.fieldModel;

    // Successfully saved.
    Drupal.ajax[base].commands.editFieldFormSaved = function (ajax, response, status) {
      Drupal.edit.util.form.unajaxifySaving($(ajax.element));

      // First, transition the state to 'saved'.
      fieldModel.set('state', 'saved');
      // Then, set the 'html' attribute on the field model. This will cause the
      // field to be rerendered.
      fieldModel.set('html', response.data);
     };

    // Unsuccessfully saved; validation errors.
    Drupal.ajax[base].commands.editFieldFormValidationErrors = function (ajax, response, status) {
      editorModel.set('validationErrors', response.data);
      fieldModel.set('state', 'invalid');
    };

    // The edit_field_form AJAX command is only called upon loading the form for
    // the first time, and when there are validation errors in the form; Form
    // API then marks which form items have errors. Therefor, we have to replace
    // the existing form, unbind the existing Drupal.ajax instance and create a
    // new Drupal.ajax instance.
    Drupal.ajax[base].commands.editFieldForm = function (ajax, response, status) {
      Drupal.edit.util.form.unajaxifySaving($(ajax.element));

      Drupal.AjaxCommands.prototype.insert(ajax, {
        data: response.data,
        selector: '#' + $formContainer.attr('id') + ' form'
      });

      // Create a Drupal.ajax instance for the re-rendered ("new") form.
      var $newSubmit = $formContainer.find('.edit-form-submit');
      Drupal.edit.util.form.ajaxifySaving({ nocssjs: false }, $newSubmit);
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
