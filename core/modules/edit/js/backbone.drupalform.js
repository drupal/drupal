/**
 * @file
 * Backbone.sync implementation for Edit. This is the beating heart.
 */
(function (jQuery, Backbone, Drupal) {

"use strict";

Backbone.defaultSync = Backbone.sync;
Backbone.sync = function(method, model, options) {
  if (options.editor.options.editorName === 'form') {
    return Backbone.syncDrupalFormWidget(method, model, options);
  }
  else {
    return Backbone.syncDirect(method, model, options);
  }
};

/**
 * Performs syncing for "form" PredicateEditor widgets.
 *
 * Implemented on top of Form API and the AJAX commands framework. Sets up
 * scoped AJAX command closures specifically for a given PredicateEditor widget
 * (which contains a pre-existing form). By submitting the form through
 * Drupal.ajax and leveraging Drupal.ajax' ability to have scoped (per-instance)
 * command implementations, we are able to update the VIE model, re-render the
 * form when there are validation errors and ensure no Drupal.ajax memory leaks.
 *
 * @see Drupal.edit.util.form
 */
Backbone.syncDrupalFormWidget = function(method, model, options) {
  if (method === 'update') {
    var predicate = options.editor.options.property;

    var $formContainer = options.editor.$formContainer;
    var $submit = $formContainer.find('.edit-form-submit');
    var base = $submit.attr('id');

    // Successfully saved.
    Drupal.ajax[base].commands.editFieldFormSaved = function(ajax, response, status) {
      Drupal.edit.util.form.unajaxifySaving(jQuery(ajax.element));

      // Call Backbone.sync's success callback with the rerendered field.
      var changedAttributes = {};
      // @todo: POSTPONED_ON(Drupal core, http://drupal.org/node/1784216)
      // Once full JSON-LD support in Drupal core lands, we can ensure that the
      // models that VIE maintains are properly updated.
      changedAttributes[predicate] = undefined;
      changedAttributes[predicate + '/rendered'] = response.data;
      options.success(changedAttributes);
    };

    // Unsuccessfully saved; validation errors.
    Drupal.ajax[base].commands.editFieldFormValidationErrors = function(ajax, response, status) {
      // Call Backbone.sync's error callback with the validation error messages.
      options.error(response.data);
    };

    // The edit_field_form AJAX command is only called upon loading the form for
    // the first time, and when there are validation errors in the form; Form
    // API then marks which form items have errors. Therefor, we have to replace
    // the existing form, unbind the existing Drupal.ajax instance and create a
    // new Drupal.ajax instance.
    Drupal.ajax[base].commands.editFieldForm = function(ajax, response, status) {
      Drupal.edit.util.form.unajaxifySaving(jQuery(ajax.element));

      Drupal.ajax.prototype.commands.insert(ajax, {
        data: response.data,
        selector: '#' + $formContainer.attr('id') + ' form'
      });

      // Create a Drupa.ajax instance for the re-rendered ("new") form.
      var $newSubmit = $formContainer.find('.edit-form-submit');
      Drupal.edit.util.form.ajaxifySaving({ nocssjs: false }, $newSubmit);
    };

    // Click the form's submit button; the scoped AJAX commands above will
    // handle the server's response.
    $submit.trigger('click.edit');
  }
};

/**
* Performs syncing for "direct" PredicateEditor widgets.
 *
 * @see Backbone.syncDrupalFormWidget()
 * @see Drupal.edit.util.form
 */
Backbone.syncDirect = function(method, model, options) {
  if (method === 'update') {
    var fillAndSubmitForm = function(value) {
      var $form = jQuery('#edit_backstage form');
        // Fill in the value in any <input> that isn't hidden or a submit button.
      $form.find(':input[type!="hidden"][type!="submit"]:not(select)')
          // Don't mess with the node summary.
          .not('[name$="\[summary\]"]').val(value);
        // Submit the form.
      $form.find('.edit-form-submit').trigger('click.edit');
    };
    var entity = options.editor.options.entity;
    var predicate = options.editor.options.property;
    var value = model.get(predicate);

    // If form doesn't already exist, load it and then submit.
    if (jQuery('#edit_backstage form').length === 0) {
      var formOptions = {
        propertyID: Drupal.edit.util.calcPropertyID(entity, predicate),
        $editorElement: options.editor.element,
        nocssjs: true
      };
      Drupal.edit.util.form.load(formOptions, function(form, ajax) {
        // Create a backstage area for storing forms that are hidden from view
        // (hence "backstage" — since the editing doesn't happen in the form, it
        // happens "directly" in the content, the form is only used for saving).
        jQuery(Drupal.theme('editBackstage', { id: 'edit_backstage' })).appendTo('body');
        // Direct forms are stuffed into #edit_backstage, apparently.
        jQuery('#edit_backstage').append(form);
        // Disable the browser's HTML5 validation; we only care about server-
        // side validation. (Not disabling this will actually cause problems
        // because browsers don't like to set HTML5 validation errors on hidden
        // forms.)
        jQuery('#edit_backstage form').attr('novalidate', true);
        var $submit = jQuery('#edit_backstage form .edit-form-submit');
        var base = Drupal.edit.util.form.ajaxifySaving(formOptions, $submit);

        // Successfully saved.
        Drupal.ajax[base].commands.editFieldFormSaved = function (ajax, response, status) {
          Drupal.edit.util.form.unajaxifySaving(jQuery(ajax.element));
          jQuery('#edit_backstage form').remove();

          // Call Backbone.sync's success callback with the rerendered field.
          var changedAttributes = {};
          // @todo: POSTPONED_ON(Drupal core, http://drupal.org/node/1784216)
          // Once full JSON-LD support in Drupal core lands, we can ensure that the
          // models that VIE maintains are properly updated.
          changedAttributes[predicate] = jQuery(response.data).find('.field-item').html();
          changedAttributes[predicate + '/rendered'] = response.data;
          options.success(changedAttributes);
        };

        // Unsuccessfully saved; validation errors.
        Drupal.ajax[base].commands.editFieldFormValidationErrors = function(ajax, response, status) {
          // Call Backbone.sync's error callback with the validation error messages.
          options.error(response.data);
        };

        // The editFieldForm AJAX command is only called upon loading the form
        // for the first time, and when there are validation errors in the form;
        // Form API then marks which form items have errors. This is useful for
        // "form" editors, but pointless for "direct" editors: the form itself
        // won't be visible at all anyway! Therefor, we ignore the new form and
        // we continue to use the existing form.
        Drupal.ajax[base].commands.editFieldForm = function(ajax, response, status) {
          // no-op
        };

        fillAndSubmitForm(value);
      });
    }
    else {
      fillAndSubmitForm(value);
    }
  }
};

})(jQuery, Backbone, Drupal);
