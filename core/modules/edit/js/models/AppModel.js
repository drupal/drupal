/**
 * @file
 * A Backbone Model for the state of the in-place editing application.
 *
 * @see Drupal.edit.AppView
 */

(function (Backbone, Drupal) {

  "use strict";

  Drupal.edit.AppModel = Backbone.Model.extend({

    defaults: {
      // The currently state = 'highlighted' Drupal.edit.FieldModel, if any.
      // @see Drupal.edit.FieldModel.states
      highlightedField: null,
      // The currently state = 'active' Drupal.edit.FieldModel, if any.
      // @see Drupal.edit.FieldModel.states
      activeField: null,
      // Reference to a Drupal.dialog instance if a state change requires
      // confirmation.
      activeModal: null
    }

  });

}(Backbone, Drupal));
