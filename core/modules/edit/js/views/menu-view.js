/**
 * @file
 * A Backbone View that provides the app-level interactive menu.
 */
(function($, _, Backbone, Drupal) {

"use strict";

Drupal.edit = Drupal.edit || {};
Drupal.edit.views = Drupal.edit.views || {};
Drupal.edit.views.MenuView = Backbone.View.extend({

  events: {
    'click #toolbar-tab-edit': 'editClickHandler'
  },

  /**
   * Implements Backbone Views' initialize() function.
   */
  initialize: function() {
    _.bindAll(this, 'stateChange');
    this.model.on('change:isViewing', this.stateChange);
    // @todo
    // Re-implement hook_toolbar and the corresponding JavaScript behaviors
    // once https://drupal.org/node/1847198 is resolved. The toolbar tray is
    // necessary when the page request is processed because its render element
    // has an #attached property with the Edit module library code assigned to
    // it. Currently a toolbar tab is not passed as a renderable array, so
    // #attached properties are not processed. The toolbar tray DOM element is
    // unnecessary right now, so it is removed.
    this.$el.find('#toolbar-tray-edit').remove();
    // Respond to clicks on other toolbar tabs. This temporary pending
    // improvements to the toolbar module.
    $('#toolbar-administration').on('click.edit', '.bar a:not(#toolbar-tab-edit)', _.bind(function (event) {
      this.model.set('isViewing', true);
    }, this));
    // We have to call stateChange() here because URL fragments are not passed
    // to the server, thus the wrong anchor may be marked as active.
    this.stateChange();
  },

  /**
   * Listens to app state changes.
   */
  stateChange: function() {
    var isViewing = this.model.get('isViewing');
    // Toggle the state of the Toolbar Edit tab based on the isViewing state.
    this.$el.find('#toolbar-tab-edit')
      .toggleClass('active', !isViewing)
      .attr('aria-pressed', !isViewing);
    // Manage the toolbar state until
    // https://drupal.org/node/1847198 is resolved
    if (!isViewing) {
      // Remove the 'toolbar-tray-open' class from the body element.
      this.$el.removeClass('toolbar-tray-open');
      // Deactivate any other active tabs and trays.
      this.$el
        .find('.bar a', '#toolbar-administration')
        .not('#toolbar-tab-edit')
        .add('.tray', '#toolbar-administration')
        .removeClass('active');
      // Set the height of the toolbar.
      if ('toolbar' in Drupal) {
        Drupal.toolbar.setHeight();
      }
    }
  },
  /**
   * Handles clicks on the edit tab of the toolbar.
   *
   * @param {Object} event
   */
  editClickHandler: function (event) {
    var isViewing = this.model.get('isViewing');
    // Toggle the href of the Toolbar Edit tab based on the isViewing state. The
    // href value should represent to state to be entered.
    this.$el.find('#toolbar-tab-edit').attr('href', (isViewing) ? '#edit' : '#view');
    this.model.set('isViewing', !isViewing);
  }
});

})(jQuery, _, Backbone, Drupal);
