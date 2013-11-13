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
    // The field is currently in the 'changed' state or one of the following
    // states in which the field is still changed.
    isChanged: false,
    // Is tracked by the EntityModel, is mirrored here solely for decorative
    // purposes: so that EditorDecorationView.renderChanged() can react to it.
    inTempStore: false,
    // The full HTML representation of this field (with the element that has
    // the data-edit-field-id as the outer element). Used to propagate changes
    // from this field instance to other instances of the same field.
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
      if (!this.get('acceptStateChange')(current, next, options, this)) {
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
    // The field associated with this FieldModel is linked to an EntityModel;
    // the user can choose to start in-place editing that entity (and
    // consequently this field). No in-place editor (EditorView) is associated
    // with this field, because this field is not being in-place edited.
    // This is both the initial (not yet in-place editing) and the end state (
    // finished in-place editing).
    'inactive',
    // The user is in-place editing this entity, and this field is a candidate
    // for in-place editing. In-place editor should not
    // - Trigger: user.
    // - Guarantees: entity is ready, in-place editor (EditorView) is associated
    //   with the field.
    // - Expected behavior: visual indicators around the field indicate it is
    //   available for in-place editing, no in-place editor presented yet.
    'candidate',
    // User is highlighting this field.
    // - Trigger: user.
    // - Guarantees: see 'candidate'.
    // - Expected behavior: visual indicators to convey highlighting, in-place
    //   editing toolbar shows field's label.
    'highlighted',
    // User has activated the in-place editing of this field; in-place editor is
    // activating.
    // - Trigger: user.
    // - Guarantees: see 'candidate'.
    // - Expected behavior: loading indicator, in-place editor is loading remote
    //   data (e.g. retrieve form from back-end). Upon retrieval of remote data,
    //   the in-place editor transitions the field's state to 'active'.
    'activating',
    // In-place editor has finished loading remote data; ready for use.
    // - Trigger: in-place editor.
    // - Guarantees: see 'candidate'.
    // - Expected behavior: in-place editor for the field is ready for use.
    'active',
    // User has modified values in the in-place editor.
    // - Trigger: user.
    // - Guarantees: see 'candidate', plus in-place editor is ready for use.
    // - Expected behavior: visual indicator of change.
    'changed',
    // User is saving changed field data in in-place editor to TempStore. The
    // save mechanism of the in-place editor is called.
    // - Trigger: user.
    // - Guarantees: see 'candidate' and 'active'.
    // - Expected behavior: saving indicator, in-place editor is saving field
    //   data into TempStore. Upon succesful saving (without validation errors),
    //   the in-place editor transitions the field's state to 'saved', but to
    //   'invalid' upon failed saving (with validation errors).
    'saving',
    // In-place editor has successfully saved the changed field.
    // - Trigger: in-place editor.
    // - Guarantees: see 'candidate' and 'active'.
    // - Expected behavior: transition back to 'candidate' state because the
    //   deed is done. Then: 1) transition to 'inactive' to allow the field to
    //   be rerendered, 2) destroy the FieldModel (which also destroys attached
    //   views like the EditorView), 3) replace the existing field HTML with the
    //   existing HTML and 4) attach behaviors again so that the field becomes
    //   available again for in-place editing.
    'saved',
    // In-place editor has failed to saved the changed field: there were
    // validation errors.
    // - Trigger: in-place editor.
    // - Guarantees: see 'candidate' and 'active'.
    // - Expected behavior: remain in 'invalid' state, let the user make more
    //   changes so that he can save it again, without validation errors.
    'invalid'
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
