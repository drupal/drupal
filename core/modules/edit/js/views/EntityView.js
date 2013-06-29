(function ($, Backbone) {

"use strict";

Drupal.edit.EntityView = Backbone.View.extend({
  /**
   * {@inheritdoc}
   *
   * Associated with the DOM root node of an editable entity.
   */
  initialize: function () {
    this.model.on('change', this.render, this);
  },

  /**
   * {@inheritdoc}
   */
  render: function () {
    this.$el.toggleClass('edit-entity-active', this.model.get('isActive'));
  },

  /**
   * {@inheritdoc}
   */
  remove: function () {
    this.setElement(null);
    Backbone.View.prototype.remove.call(this);
  }
});

}(jQuery, Backbone));
