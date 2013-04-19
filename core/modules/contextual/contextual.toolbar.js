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
    var that = this;
    $('body').once('contextualToolbar-init', function () {
      var options = $.extend({}, that.defaults);
      var $contextuals = $(context).find('.contextual-links');
      var $tab = $('.js .toolbar .bar .contextual-toolbar-tab');
      var model = new Drupal.contextualToolbar.models.EditToggleModel({
        isViewing: true,
        contextuals: $contextuals.get()
      });
      var view = new Drupal.contextualToolbar.views.EditToggleView({
        el: $tab,
        model: model,
        strings: options.strings
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
  },

  defaults: {
    strings: {
      tabbingReleased: Drupal.t('Tabbing is no longer constrained by the Contextual module'),
      tabbingConstrained: Drupal.t('Tabbing is constrained to a set of @contextualsCount and the Edit mode toggle'),
      pressEsc: Drupal.t('Press the esc key to exit.'),
      contextualsCount: {
        singular: '@count contextual link',
        plural: '@count contextual links'
      }
    }
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
    isVisible: false,
    // The set of elements that can be reached via the tab key when edit mode
    // is enabled.
    tabbingContext: null,
    // The set of contextual links stored as an Array.
    contextuals: []
  }
});

/**
 * Handles edit mode toggle interactions.
 */
Drupal.contextualToolbar.views.EditToggleView = Backbone.View.extend({

  events: { 'click': 'onClick' },

  // Tracks whether the tabbing constraint announcement has been read once yet.
  announcedOnce: false,

  /**
   * Implements Backbone Views' initialize().
   */
  initialize: function () {

    this.strings = this.options.strings;

    this.model.on('change', this.render, this);
    this.model.on('change:isViewing', this.persist, this);
    this.model.on('change:isViewing', this.manageTabbing, this);

    $(document)
      .on('keyup', $.proxy(this.onKeypress, this));
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
   * Limits tabbing to the contextual links and edit mode toolbar tab.
   *
   * @param Drupal.contextualToolbar.models.EditToggleModel model
   *   An EditToggleModel Backbone model.
   * @param bool isViewing
   *   The value of the isViewing attribute in the model.
   */
  manageTabbing: function (model, isViewing) {
    var tabbingContext = this.model.get('tabbingContext');
    // Always release an existing tabbing context.
    if (tabbingContext) {
      tabbingContext.release();
    }
    // Create a new tabbing context when edit mode is enabled.
    if (!isViewing) {
      tabbingContext = Drupal.tabbingManager.constrain($('.contextual-toolbar-tab, .contextual'));
      this.model.set('tabbingContext', tabbingContext);
    }
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

  /**
   * Passes state update messsages to Drupal.announce.
   */
  announceTabbingConstraint: function () {
    var isViewing = this.model.get('isViewing');

    if (!isViewing) {
      var contextuals = this.model.get('contextuals');
      Drupal.announce(Drupal.t(this.strings.tabbingConstrained, {
        '@contextualsCount': Drupal.formatPlural(contextuals.length, this.strings.contextualsCount.singular, this.strings.contextualsCount.plural)
      }));
      Drupal.announce(this.strings.pressEsc);
    }
    else {
      Drupal.announce(this.strings.tabbingReleased)
    }
  },

  /**
   * Responds to the edit mode toggle toolbar button; Toggles edit mode.
   *
   * @param jQuery.Event event
   */
  onClick: function (event) {
    this.model.set('isViewing', !this.model.get('isViewing'));
    this.announceTabbingConstraint();
    this.announcedOnce = true;
    event.preventDefault();
    event.stopPropagation();
  },

  /**
   * Responds to esc and tab key press events.
   *
   * @param jQuery.Event event
   */
  onKeypress: function (event) {
    // Respond to tab key press; Call render so the state announcement is read.
    // The first tab key press is tracked so that an annoucement about tabbing
    // constraints can be raised if edit mode is enabled when this page loads.
    if (!this.announcedOnce && event.keyCode === 9 && !this.model.get('isViewing')) {
      this.announceTabbingConstraint();
      // Set announce to true so that this conditional block won't be run again.
      this.announcedOnce = true;
    }
    // Respond to the ESC key. Exit out of edit mode.
    if (event.keyCode === 27) {
      this.model.set('isViewing', true);
    }
  }
});

})(jQuery, Backbone, Drupal, document, localStorage);
