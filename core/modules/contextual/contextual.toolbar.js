/**
 * @file
 * Attaches behaviors for the Contextual module's edit toolbar tab.
 */

(function ($, Backbone, Drupal, document, localStorage) {

"use strict";

/**
 * Attaches contextual's edit toolbar tab behavior.
 *
 * Events
 *   Contextual triggers an event that can be used by other scripts.
 *   - drupalEditModeChanged: Triggered when the edit mode changes.
 */
Drupal.behaviors.contextualToolbar = {
  attach: function (context) {
    $('body').once('contextualToolbar-init', function () {
      var $contextuals = $(context).find('.contextual-links');
      var $tab = $('.js .toolbar .bar .contextual-toolbar-tab');
      var model = new Drupal.contextualToolbar.models.EditToggleModel({
        isViewing: true
      });
      var view = new Drupal.contextualToolbar.views.EditToggleView({
        el: $tab,
        model: model
      });

      // Update the model based on overlay events.
      $(document)
        .on('drupalOverlayOpen.contextualToolbar', function () {
          model.set('isVisible', false);
        })
        .on('drupalOverlayClose.contextualToolbar', function () {
          model.set('isVisible', true);
        });

      // Update the model to show the edit tab if there's >=1 contextual link.
      if ($contextuals.length > 0) {
        model.set('isVisible', true);
      }

      // Allow other scripts to respond to edit mode changes.
      model.on('change:isViewing', function (model, value) {
        $(document).trigger('drupalEditModeChanged', { status: !value });
      });

      // Checks whether localStorage indicates we should start in edit mode
      // rather than view mode.
      // @see Drupal.contextualToolbar.views.EditToggleView.persist()
      if (localStorage.getItem('Drupal.contextualToolbar.isViewing') !== null) {
        model.set('isViewing', false);
      }
    });
  }
};

Drupal.contextualToolbar = Drupal.contextualToolbar || { models: {}, views: {}};

/**
 * Backbone Model for the edit toggle.
 */
Drupal.contextualToolbar.models.EditToggleModel = Backbone.Model.extend({
  defaults: {
    // Indicates whether the toggle is currently in "view" or "edit" mode.
    isViewing: true,
    // Indicates whether the toggle should be visible or hidden.
    isVisible: false
  }
});

/**
 * Handles edit mode toggle interactions.
 */
Drupal.contextualToolbar.views.EditToggleView = Backbone.View.extend({

  events: { 'click': 'onClick' },

  /**
   * Implements Backbone Views' initialize().
   */
  initialize: function () {
    this.model.on('change', this.render, this);
    this.model.on('change:isViewing', this.persist, this);
  },

  /**
   * Implements Backbone Views' render().
   */
  render: function () {
    var args = arguments;
    // Render the visibility.
    this.$el.toggleClass('element-hidden', !this.model.get('isVisible'));

    // Render the state.
    var isViewing = this.model.get('isViewing');
    this.$el.find('button')
      .toggleClass('active', !isViewing)
      .attr('aria-pressed', !isViewing);

    return this;
  },

  /**
   * Model change handler; persists the isViewing value to localStorage.
   *
   * isViewing === true is the default, so only stores in localStorage when
   * it's not the default value (i.e. false).
   *
   * @param Drupal.contextualToolbar.models.EditToggleModel model
   *   An EditToggleModel Backbone model.
   * @param bool isViewing
   *   The value of the isViewing attribute in the model.
   */
  persist: function (model, isViewing) {
    if (!isViewing) {
      localStorage.setItem('Drupal.contextualToolbar.isViewing', 'false');
    }
    else {
      localStorage.removeItem('Drupal.contextualToolbar.isViewing');
    }
  },

  onClick: function (event) {
    this.model.set('isViewing', !this.model.get('isViewing'));
    event.preventDefault();
    event.stopPropagation();
  }
});

})(jQuery, Backbone, Drupal, document, localStorage);
