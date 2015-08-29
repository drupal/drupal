/**
 * @file
 * A Backbone View that renders the visual view of a contextual region element.
 */

(function (Drupal, Backbone, Modernizr) {

  "use strict";

  Drupal.contextual.RegionView = Backbone.View.extend(/** @lends Drupal.contextual.RegionView# */{

    /**
     * Events for the Backbone view.
     *
     * @return {object}
     *   A mapping of events to be used in the view.
     */
    events: function () {
      var mapping = {
        mouseenter: function () { this.model.set('regionIsHovered', true); },
        mouseleave: function () {
          this.model.close().blur().set('regionIsHovered', false);
        }
      };
      // We don't want mouse hover events on touch.
      if (Modernizr.touch) {
        mapping = {};
      }
      return mapping;
    },

    /**
     * Renders the visual view of a contextual region element.
     *
     * @constructs
     *
     * @augments Backbone.View
     */
    initialize: function () {
      this.listenTo(this.model, 'change:hasFocus', this.render);
    },

    /**
     * @inheritdoc
     *
     * @return {Drupal.contextual.RegionView}
     *   The current contextual region view.
     */
    render: function () {
      this.$el.toggleClass('focus', this.model.get('hasFocus'));

      return this;
    }

  });

})(Drupal, Backbone, Modernizr);
