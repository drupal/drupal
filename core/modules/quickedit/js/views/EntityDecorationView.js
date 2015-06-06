/**
 * @file
 * A Backbone view that decorates the in-place editable entity.
 */

(function (Drupal, $, Backbone) {

  "use strict";

  Drupal.quickedit.EntityDecorationView = Backbone.View.extend(/** @lends Drupal.quickedit.EntityDecorationView# */{

    /**
     * Associated with the DOM root node of an editable entity.
     *
     * @constructs
     *
     * @augments Backbone.View
     */
    initialize: function () {
      this.listenTo(this.model, 'change', this.render);
    },

    /**
     * @inheritdoc
     */
    render: function () {
      this.$el.toggleClass('quickedit-entity-active', this.model.get('isActive'));
    },

    /**
     * @inheritdoc
     */
    remove: function () {
      this.setElement(null);
      Backbone.View.prototype.remove.call(this);
    }

  });

}(Drupal, jQuery, Backbone));
