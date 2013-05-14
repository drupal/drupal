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
        this.model.set('isActive', !this.model.get('isActive'));
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
    // Initial render.
    this.render();

    // Re-render whenever this entity's isActive attribute changes.
    this.model.on('change:isActive', this.render, this);

    // Hide the contextual links whenever an in-place editor is active.
    this.options.appModel.on('change:activeEditor', this.toggleContextualLinksVisibility, this);
  },

  /**
   * {@inheritdoc}
   */
  render: function () {
    var strings = this.options.strings;
    var text = !this.model.get('isActive') ? strings.quickEdit : strings.stopQuickEdit;
    this.$el.find('a').text(text);
    return this;
  },

  /**
   * Hides the contextual links if an in-place editor is active.
   *
   * @param Drupal.edit.AppModel appModel
   *   The application state model.
   * @param null|Drupal.edit.FieldModel activeEditor
   *   The model of the field that is currently being edited, or, if none, null.
   */
  toggleContextualLinksVisibility: function (appModel, activeEditor) {
    this.$el.parents('.contextual').toggle(activeEditor === null);
  }

});

})(jQuery, _, Backbone, Drupal);
