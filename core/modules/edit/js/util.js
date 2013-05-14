/**
 * @file
 * Provides utility functions for Edit.
 */
(function ($, _, Drupal, drupalSettings) {

"use strict";

Drupal.edit.util = Drupal.edit.util || {};

Drupal.edit.util.constants = {};
Drupal.edit.util.constants.transitionEnd = "transitionEnd.edit webkitTransitionEnd.edit transitionend.edit msTransitionEnd.edit oTransitionEnd.edit";

/**
 * Converts a field id into a formatted url path.
 *
 * @param String id
 *   The id of an editable field. For example, 'node/1/body/und/full'.
 * @param String urlFormat
 *   The Controller route for field processing. For example,
 *   '/edit/form/%21entity_type/%21id/%21field_name/%21langcode/%21view_mode'.
 */
Drupal.edit.util.buildUrl = function (id, urlFormat) {
  var parts = id.split('/');
  return Drupal.formatString(decodeURIComponent(urlFormat), {
    '!entity_type': parts[0],
    '!id'         : parts[1],
    '!field_name' : parts[2],
    '!langcode'   : parts[3],
    '!view_mode'  : parts[4]
  });
};

Drupal.edit.util.form = {
  /**
   * Loads a form, calls a callback to inserts.
   *
   * Leverages Drupal.ajax' ability to have scoped (per-instance) command
   * implementations to be able to call a callback.
   *
   * @param Object options
   *   An object with the following keys:
   *    - jQuery $el: (required) DOM element necessary for Drupal.ajax to
   *      perform AJAX commands.
   *    - String fieldID: (required) the field ID that uniquely identifies the
   *      field for which this form will be loaded.
   *    - Boolean nocssjs: (required) boolean indicating whether no CSS and JS
   *      should be returned (necessary when the form is invisible to the user).
   * @param Function callback
   *   A callback function that will receive the form to be inserted, as well as
   *   the ajax object, necessary if the callback wants to perform other AJAX
   *   commands.
   */
  load: function (options, callback) {
    var $el = options.$el;
    var fieldID = options.fieldID;

    // Create a Drupal.ajax instance to load the form.
    Drupal.ajax[fieldID] = new Drupal.ajax(fieldID, $el, {
      url: Drupal.edit.util.buildUrl(fieldID, drupalSettings.edit.fieldFormURL),
      event: 'edit-internal.edit',
      submit: { nocssjs : options.nocssjs },
      progress: { type : null } // No progress indicator.
    });
    // Implement a scoped editFieldForm AJAX command: calls the callback.
    Drupal.ajax[fieldID].commands.editFieldForm = function (ajax, response, status) {
      callback(response.data, ajax);
      // Delete the Drupal.ajax instance that called this very function.
      delete Drupal.ajax[fieldID];
      $el.off('edit-internal.edit');
    };
    // This will ensure our scoped editFieldForm AJAX command gets called.
    $el.trigger('edit-internal.edit');
  },

  /**
   * Creates a Drupal.ajax instance that is used to save a form.
   *
   * @param Object options
   *   An object with the following keys:
   *    - nocssjs: (required) boolean indicating whether no CSS and JS should be
   *      returned (necessary when the form is invisible to the user).
   * @return String
   *   The key of the Drupal.ajax instance.
   */
  ajaxifySaving: function (options, $submit) {
    // Re-wire the form to handle submit.
    var element_settings = {
      url: $submit.closest('form').attr('action'),
      setClick: true,
      event: 'click.edit',
      progress: { type: null },
      submit: { nocssjs : options.nocssjs }
    };
    var base = $submit.attr('id');

    Drupal.ajax[base] = new Drupal.ajax(base, $submit[0], element_settings);
    // Reimplement the success handler to ensure Drupal.attachBehaviors() does
    // not get called on the form.
    Drupal.ajax[base].success = function (response, status) {
      for (var i in response) {
        if (response.hasOwnProperty(i) && response[i].command && this.commands[response[i].command]) {
          this.commands[response[i].command](this, response[i], status);
        }
      }
    };

    return base;
  },

  /**
   * Cleans up the Drupal.ajax instance that is used to save the form.
   *
   * @param jQuery $submit
   *   The jQuery-wrapped submit DOM element that should be unajaxified.
   */
  unajaxifySaving: function ($submit) {
    delete Drupal.ajax[$submit.attr('id')];
    $submit.off('click.edit');
  }
};

})(jQuery, _, Drupal, drupalSettings);
