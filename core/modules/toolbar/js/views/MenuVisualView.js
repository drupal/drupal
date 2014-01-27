/**
 * @file
 * A Backbone view for the collapsible menus.
 */

(function ($, Backbone, Drupal) {

  "use strict";

  /**
   * Backbone View for collapsible menus.
   */
  Drupal.toolbar.MenuVisualView = Backbone.View.extend({
    /**
     * {@inheritdoc}
     */
    initialize: function () {
      this.listenTo(this.model, 'change:subtrees', this.render);
    },

    /**
     * {@inheritdoc}
     */
    render: function () {
      var subtrees = this.model.get('subtrees');
      // Add subtrees.
      for (var id in subtrees) {
        if (subtrees.hasOwnProperty(id)) {
          this.$el
            .find('#toolbar-link-' + id)
            .once('toolbar-subtrees')
            .after(subtrees[id]);
        }
      }
      // Render the main menu as a nested, collapsible accordion.
      if ('drupalToolbarMenu' in $.fn) {
        this.$el
          .children('.menu')
          .drupalToolbarMenu();
      }
    }
  });

}(jQuery, Backbone, Drupal));
