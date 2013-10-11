/**
 * @file
 * A Backbone View that a dynamic contextual link.
 */
(function ($, _, Backbone, Drupal) {

"use strict";

Drupal.edit.ContextualLinkView = Backbone.View.extend({

   events: function () {
    // Prevents delay and simulated mouse events.
    function touchEndToClick (event) {
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
    this.$el.find('a').text(this.options.strings.quickEdit);
    // Initial render.
    this.render();
    // Re-render whenever this entity's isActive attribute changes.
    this.model.on('change:isActive', this.render, this);
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

})(jQuery, _, Backbone, Drupal);
