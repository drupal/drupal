/**
 * @file
 * Provides utility functions for Edit.
 */

(function ($, Drupal) {

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
      '!id': parts[1],
      '!field_name': parts[2],
      '!langcode': parts[3],
      '!view_mode': parts[4]
    });
  };

  /**
   * Shows a network error modal dialog.
   *
   * @param String title
   *   The title to use in the modal dialog.
   * @param String message
   *   The message to use in the modal dialog.
   */
  Drupal.edit.util.networkErrorModal = function (title, message) {
    var $message = $('<div>' + message + '</div>');
    var networkErrorModal = Drupal.dialog($message.get(0), {
      title: title,
      dialogClass: 'edit-network-error',
      buttons: [
        {
          text: Drupal.t('OK'),
          click: function () {
            networkErrorModal.close();
          }
        }
      ],
      create: function () {
        $(this).parent().find('.ui-dialog-titlebar-close').remove();
      },
      close: function (event) {
        // Automatically destroy the DOM element that was used for the dialog.
        $(event.target).remove();
      }
    });
    networkErrorModal.showModal();
  };

  Drupal.edit.util.form = {

    /**
     * Loads a form, calls a callback to insert.
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
     *    - Boolean reset: (required) boolean indicating whether the data stored
     *      for this field's entity in TempStore should be used or reset.
     * @param Function callback
     *   A callback function that will receive the form to be inserted, as well as
     *   the ajax object, necessary if the callback wants to perform other AJAX
     *   commands.
     */
    load: function (options, callback) {
      var $el = options.$el;
      var fieldID = options.fieldID;

      // Create a Drupal.ajax instance to load the form.
      var formLoaderAjax = new Drupal.ajax(fieldID, $el, {
        url: Drupal.edit.util.buildUrl(fieldID, Drupal.url('edit/form/!entity_type/!id/!field_name/!langcode/!view_mode')),
        event: 'edit-internal.edit',
        submit: {
          nocssjs: options.nocssjs,
          reset: options.reset
        },
        progress: { type: null }, // No progress indicator.
        error: function (xhr, url) {
          $el.off('edit-internal.edit');

          // Show a modal to inform the user of the network error.
          var fieldLabel = Drupal.edit.metadata.get(fieldID, 'label');
          var message = Drupal.t('Could not load the form for <q>@field-label</q>, either due to a website problem or a network connection problem.<br>Please try again.', { '@field-label': fieldLabel });
          Drupal.edit.util.networkErrorModal(Drupal.t('Sorry!'), message);

          // Change the state back to "candidate", to allow the user to start
          // in-place editing of the field again.
          var fieldModel = Drupal.edit.app.model.get('activeField');
          fieldModel.set('state', 'candidate');
        }
      });
      // Implement a scoped editFieldForm AJAX command: calls the callback.
      formLoaderAjax.commands.editFieldForm = function (ajax, response, status) {
        callback(response.data, ajax);
        $el.off('edit-internal.edit');
        formLoaderAjax = null;
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
     *    - other_view_modes: (required) array containing view mode IDs (of other
     *      instances of this field on the page).
     * @return Drupal.ajax
     *   A Drupal.ajax instance.
     */
    ajaxifySaving: function (options, $submit) {
      // Re-wire the form to handle submit.
      var settings = {
        url: $submit.closest('form').attr('action'),
        setClick: true,
        event: 'click.edit',
        progress: { type: null },
        submit: {
          nocssjs: options.nocssjs,
          other_view_modes: options.other_view_modes
        },
        // Reimplement the success handler to ensure Drupal.attachBehaviors() does
        // not get called on the form.
        success: function (response, status) {
          for (var i in response) {
            if (response.hasOwnProperty(i) && response[i].command && this.commands[response[i].command]) {
              this.commands[response[i].command](this, response[i], status);
            }
          }
        }
      };

      return new Drupal.ajax($submit.attr('id'), $submit[0], settings);
    },

    /**
     * Cleans up the Drupal.ajax instance that is used to save the form.
     *
     * @param Drupal.ajax ajax
     *   A Drupal.ajax that was returned by Drupal.edit.form.ajaxifySaving().
     */
    unajaxifySaving: function (ajax) {
      $(ajax.element).off('click.edit');
    }

  };

})(jQuery, Drupal);
