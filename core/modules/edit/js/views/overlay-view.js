/**
 * @file
 * A Backbone View that provides the app-level overlay.
 *
 * The overlay sits on top of the existing content, the properties that are
 * candidates for editing sit on top of the overlay.
 */
(function ($, _, Backbone, Drupal) {

"use strict";

Drupal.edit = Drupal.edit || {};
Drupal.edit.views = Drupal.edit.views || {};
Drupal.edit.views.OverlayView = Backbone.View.extend({

  events: {
    'click': 'onClick'
  },

  /**
   * Implements Backbone Views' initialize() function.
   */
  initialize: function (options) {
    _.bindAll(this, 'stateChange');
    this.model.on('change:isViewing', this.stateChange);
    // Add the overlay to the page.
    this.$el
      .addClass('edit-animate-slow edit-animate-invisible')
      .hide()
      .appendTo('body');
  },

  /**
   * Listens to app state changes.
   */
  stateChange: function () {
    if (this.model.get('isViewing')) {
      this.remove();
      return;
    }
    this.render();
  },

  /**
   * Equates clicks anywhere on the overlay to clicking the active editor's (if
   * any) "close" button.
   *
   * @param {Object} event
   */
  onClick: function (event) {
    event.preventDefault();
    var activeEditor = this.model.get('activeEditor');
    if (activeEditor) {
      var editableEntity = activeEditor.options.widget;
      var predicate = activeEditor.options.property;
      editableEntity.setState('candidate', predicate, { reason: 'overlay' });
    }
    else {
      this.model.set('isViewing', true);
    }
  },

  /**
   * Reveal the overlay element.
   */
  render: function () {
    this.$el
      .show()
      .css('top', $('#navbar').outerHeight())
      .removeClass('edit-animate-invisible');
  },

  /**
   * Hide the overlay element.
   */
  remove: function () {
    var that = this;
    this.$el
      .addClass('edit-animate-invisible')
      .on(Drupal.edit.util.constants.transitionEnd, function (event) {
        that.$el.hide();
      });
  }
});

})(jQuery, _, Backbone, Drupal);
