(function (Backbone, Drupal) {

"use strict";

/**
 * State of an in-place editable entity in the DOM.
 */
Drupal.edit.EntityModel = Backbone.Model.extend({

  defaults: {
    // The DOM element that represents this entity. It may seem bizarre to
    // have a DOM element in a Backbone Model, but we need to be able to map
    // entities in the DOM to EntityModels in memory.
    el: null,
    // An entity ID, of the form "<entity type>/<entity ID>", e.g. "node/1".
    id: null,
    // A Drupal.edit.FieldCollection for all fields of this entity.
    fields: null,

    // The attributes below are stateful. The ones above will never change
    // during the life of a EntityModel instance.

    // Indicates whether this instance of this entity is currently being
    // edited in-place.
    isActive: false
  },

  /**
   * @inheritdoc
   */
  initialize: function () {
    this.set('fields', new Drupal.edit.FieldCollection());
  },

  /**
   * @inheritdoc
   */
  destroy: function (options) {
    Backbone.Model.prototype.destroy.apply(this, options);

    // Destroy all fields of this entity.
    this.get('fields').each(function (fieldModel) {
      fieldModel.destroy();
    });
  },

  /**
   * {@inheritdoc}
   */
  sync: function () {
    // We don't use REST updates to sync.
    return;
  }

});

Drupal.edit.EntityCollection = Backbone.Collection.extend({
  model: Drupal.edit.EntityModel
});

}(Backbone, Drupal));
