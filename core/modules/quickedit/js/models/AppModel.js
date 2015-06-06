/**
 * @file
 * A Backbone Model for the state of the in-place editing application.
 *
 * @see Drupal.quickedit.AppView
 */

(function (Backbone, Drupal) {

  "use strict";

  /**
   * @constructor
   *
   * @augments Backbone.Model
   */
  Drupal.quickedit.AppModel = Backbone.Model.extend(/** @lends Drupal.quickedit.AppModel# */{

    /**
     * @type {object}
     *
     * @prop {Drupal.quickedit.FieldModel} highlightedField
     * @prop {Drupal.quickedit.FieldModel} activeField
     * @prop {Drupal.dialog~dialogDefinition} activeModal
     */
    defaults: /** @lends Drupal.quickedit.AppModel# */{

      /**
       * The currently state='highlighted' Drupal.quickedit.FieldModel, if any.
       *
       * @type {Drupal.quickedit.FieldModel}
       *
       * @see Drupal.quickedit.FieldModel.states
       */
      highlightedField: null,

      /**
       * The currently state = 'active' Drupal.quickedit.FieldModel, if any.
       *
       * @type {Drupal.quickedit.FieldModel}
       *
       * @see Drupal.quickedit.FieldModel.states
       */
      activeField: null,

      /**
       * Reference to a {@link Drupal.dialog} instance if a state change
       * requires confirmation.
       *
       * @type {Drupal.dialog~dialogDefinition}
       */
      activeModal: null
    }

  });

}(Backbone, Drupal));
