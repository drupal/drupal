(function (_, Backbone, Drupal) {

"use strict";

/**
 * State of an in-place editable field in the DOM.
 */
Drupal.edit.FieldModel = Backbone.Model.extend({

  defaults: {
    // The DOM element that represents this field. It may seem bizarre to have
    // a DOM element in a Backbone Model, but we need to be able to map fields
    // in the DOM to FieldModels in memory.
    el: null,
    // A field ID, of the form
    // "<entity type>/<id>/<field name>/<language>/<view mode>", e.g.
    // "node/1/field_tags/und/full".
    id: null,
    // A Drupal.edit.EntityModel. Its "fields" attribute, which is a
    // FieldCollection, is automatically updated to include this FieldModel.
    entity: null,
    // This field's metadata as returned by the EditController::metadata().
    metadata: null,
    // Callback function for validating changes between states. Receives the
    // previous state, new state, context, and a callback
    acceptStateChange: null,

    // The attributes below are stateful. The ones above will never change
    // during the life of a FieldModel instance.

    // In-place editing state of this field. Defaults to the initial state.
    // Possible values: @see Drupal.edit.FieldModel.states.
    state: 'inactive',
    // The full HTML representation of this field (with the element that has
    // the data-edit-id as the outer element). Used to propagate changes from
    // this field instance to other instances of the same field.
    html: null
  },

  /**
   * {@inheritdoc}
   */
  initialize: function (options) {
    // Store the original full HTML representation of this field.
    this.set('html', options.el.outerHTML);

    // Enlist field automatically in the associated entity's field collection.
    this.get('entity').get('fields').add(this);
  },

  /**
   * {@inheritdoc}
   */
  destroy: function (options) {
    if (this.get('state') !== 'inactive') {
      throw new Error("FieldModel cannot be destroyed if it is not inactive state.");
    }
    Backbone.Model.prototype.destroy.apply(this, options);
  },

  /**
   * {@inheritdoc}
   */
  sync: function () {
    // We don't use REST updates to sync.
    return;
  },

  /**
   * {@inheritdoc}
   */
  validate: function (attrs, options) {
    // We only care about validating the 'state' attribute.
    if (!_.has(attrs, 'state')) {
      return;
    }

    var current = this.get('state');
    var next = attrs.state;
    if (current !== next) {
      // Ensure it's a valid state.
      if (_.indexOf(this.constructor.states, next) === -1) {
        return '"' + next + '" is an invalid state';
      }
      // Check if the acceptStateChange callback accepts it.
      if (!this.get('acceptStateChange')(current, next, options)) {
        return 'state change not accepted';
      }
    }
  },

  /**
   * Extracts the entity ID from this field's ID.
   *
   * @return String
   *   An entity ID: a string of the format `<entity type>/<id>`.
   */
  getEntityID: function () {
    return this.id.split('/').slice(0, 2).join('/');
  }

}, {

  /**
   * A list (sequence) of all possible states a field can be in during in-place
   * editing.
   */
  states: [
    'inactive', 'candidate', 'highlighted',
    'activating', 'active', 'changed', 'saving', 'saved', 'invalid'
  ],

  /**
   * Indicates whether the 'from' state comes before the 'to' state.
   *
   * @param String from
   *   One of Drupal.edit.FieldModel.states.
   * @param String to
   *   One of Drupal.edit.FieldModel.states.
   * @return Boolean
   */
  followsStateSequence: function (from, to) {
    return _.indexOf(this.states, from) < _.indexOf(this.states, to);
  }

});

Drupal.edit.FieldCollection = Backbone.Collection.extend({
  model: Drupal.edit.FieldModel
});

}(_, Backbone, Drupal));
