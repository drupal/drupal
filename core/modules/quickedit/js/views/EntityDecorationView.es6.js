/**
 * @file
 * A Backbone view that decorates the in-place editable entity.
 */

(function (Drupal, $, Backbone) {
  Drupal.quickedit.EntityDecorationView = Backbone.View.extend(/** @lends Drupal.quickedit.EntityDecorationView# */{

    /**
     * Associated with the DOM root node of an editable entity.
     *
     * @constructs
     *
     * @augments Backbone.View
     */
    initialize() {
      this.listenTo(this.model, 'change', this.render);
    },

    /**
     * @inheritdoc
     */
    render() {
      this.$el.toggleClass('quickedit-entity-active', this.model.get('isActive'));
    },

    /**
     * @inheritdoc
     */
    remove() {
      this.setElement(null);
      Backbone.View.prototype.remove.call(this);
    },

  });
}(Drupal, jQuery, Backbone));
