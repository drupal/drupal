/**
 * @file
 * A Backbone view that decorates the in-place editable entity.
 */

(function (Drupal, $, Backbone) {

  "use strict";

  Drupal.edit.EntityDecorationView = Backbone.View.extend({

    /**
     * {@inheritdoc}
     *
     * Associated with the DOM root node of an editable entity.
     */
    initialize: function () {
      this.listenTo(this.model, 'change', this.render);
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

}(Drupal, jQuery, Backbone));
