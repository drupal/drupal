/**
 * @file
 * A Backbone View that provides a dynamic contextual link.
 */

(function ($, Backbone, Drupal) {

  "use strict";

  Drupal.edit.ContextualLinkView = Backbone.View.extend({

    events: function () {
      // Prevents delay and simulated mouse events.
      function touchEndToClick(event) {
        event.preventDefault();
        event.target.click();
      }
      return {
        'click a': function (event) {
          event.preventDefault();
          this.model.set('state', 'launching');
        },
        'touchEnd a': touchEndToClick
      };
    },

    /**
     * {@inheritdoc}
     *
     * @param Object options
     *   An object with the following keys:
     *   - Drupal.edit.EntityModel model: the associated entity's model
     *   - Drupal.edit.AppModel appModel: the application state model
     *   - strings: the strings for the "Quick edit" link
     */
    initialize: function (options) {
      // Insert the text of the quick edit toggle.
      this.$el.find('a').text(options.strings.quickEdit);
      // Initial render.
      this.render();
      // Re-render whenever this entity's isActive attribute changes.
      this.listenTo(this.model, 'change:isActive', this.render);
    },

    /**
     * {@inheritdoc}
     */
    render: function (entityModel, isActive) {
      this.$el.find('a').attr('aria-pressed', isActive);

      // Hides the contextual links if an in-place editor is active.
      this.$el.closest('.contextual').toggle(!isActive);

      return this;
    }

  });

})(jQuery, Backbone, Drupal);
