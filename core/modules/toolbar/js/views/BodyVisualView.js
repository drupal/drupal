/**
 * @file
 * A Backbone view for the body element.
 */

(function ($, Drupal, Backbone) {

  "use strict";

  /**
   * Adjusts the body element with the toolbar position and dimension changes.
   */
  Drupal.toolbar.BodyVisualView = Backbone.View.extend({

    /**
     * {@inheritdoc}
     */
    initialize: function () {
      this.listenTo(this.model, 'change:orientation change:offsets change:activeTray change:isOriented change:isFixed change:isViewportOverflowConstrained', this.render);
    },

    /**
     * {@inheritdoc}
     */
    render: function () {
      var $body = $('body');
      var orientation = this.model.get('orientation');
      var isOriented = this.model.get('isOriented');
      var isViewportOverflowConstrained = this.model.get('isViewportOverflowConstrained');

      $body
        // We are using JavaScript to control media-query handling for two
        // reasons: (1) Using JavaScript let's us leverage the breakpoint
        // configurations and (2) the CSS is really complex if we try to hide
        // some styling from browsers that don't understand CSS media queries.
        // If we drive the CSS from classes added through JavaScript,
        // then the CSS becomes simpler and more robust.
        .toggleClass('toolbar-vertical', (orientation === 'vertical'))
        .toggleClass('toolbar-horizontal', (isOriented && orientation === 'horizontal'))
        // When the toolbar is fixed, it will not scroll with page scrolling.
        .toggleClass('toolbar-fixed', (isViewportOverflowConstrained || this.model.get('isFixed')))
        // Toggle the toolbar-tray-open class on the body element. The class is
        // applied when a toolbar tray is active. Padding might be applied to
        // the body element to prevent the tray from overlapping content.
        .toggleClass('toolbar-tray-open', !!this.model.get('activeTray'))
        // Apply padding to the top of the body to offset the placement of the
        // toolbar bar element.
        .css('padding-top', this.model.get('offsets').top);
    }
  });

}(jQuery, Drupal, Backbone));
