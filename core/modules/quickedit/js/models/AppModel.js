/**
 * @file
 * A Backbone Model for the state of the in-place editing application.
 *
 * @see Drupal.quickedit.AppView
 */

(function (Backbone, Drupal) {

  "use strict";

  Drupal.quickedit.AppModel = Backbone.Model.extend({

    defaults: {
      // The currently state = 'highlighted' Drupal.quickedit.FieldModel, if
      // any.
      // @see Drupal.quickedit.FieldModel.states
      highlightedField: null,
      // The currently state = 'active' Drupal.quickedit.FieldModel, if any.
      // @see Drupal.quickedit.FieldModel.states
      activeField: null,
      // Reference to a Drupal.dialog instance if a state change requires
      // confirmation.
      activeModal: null
    }

  });

}(Backbone, Drupal));
