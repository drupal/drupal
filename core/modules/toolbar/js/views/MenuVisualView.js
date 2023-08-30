/**
 * @file
 * A Backbone view for the collapsible menus.
 */

(function ($, Backbone, Drupal) {
  Drupal.toolbar.MenuVisualView = Backbone.View.extend(
    /** @lends Drupal.toolbar.MenuVisualView# */ {
      /**
       * Backbone View for collapsible menus.
       *
       * @constructs
       *
       * @augments Backbone.View
       */
      initialize() {
        this.listenTo(this.model, 'change:subtrees', this.render);

        // Render the view immediately on initialization.
        this.render();
      },

      /**
       * {@inheritdoc}
       */
      render() {
        this.renderVertical();
        this.renderHorizontal();
      },

      /**
       * Renders the toolbar menu in horizontal mode.
       */
      renderHorizontal() {
        // Render horizontal.
        if ('drupalToolbarMenu' in $.fn) {
          this.$el.children('.toolbar-menu').drupalToolbarMenuHorizontal();
        }
      },

      /**
       * Renders the toolbar menu in vertical mode.
       */
      renderVertical() {
        const subtrees = this.model.get('subtrees');

        // Rendering the vertical menu depends on the subtrees.
        if (!this.model.get('subtrees')) {
          return;
        }

        // Add subtrees.
        Object.keys(subtrees || {}).forEach((id) => {
          $(
            once('toolbar-subtrees', this.$el.find(`#toolbar-link-${id}`)),
          ).after(subtrees[id]);
        });
        // Render the main menu as a nested, collapsible accordion.
        if ('drupalToolbarMenu' in $.fn) {
          this.$el.children('.toolbar-menu').drupalToolbarMenu();
        }
      },
    },
  );
})(jQuery, Backbone, Drupal);
