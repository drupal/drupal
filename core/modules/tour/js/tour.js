/**
 * @file
 * Attaches behaviors for the Tour module's toolbar tab.
 */

(function ($, Backbone, Drupal, document) {

"use strict";

/**
 * Attaches the tour's toolbar tab behavior.
 *
 * It uses the query string for:
 * - tour: When ?tour=1 is present, the tour will start automatically
 *         after the page has loaded.
 * - tips: Pass ?tips=class in the url to filter the available tips to
 *         the subset which match the given class.
 *
 * Example:
 *   http://example.com/foo?tour=1&tips=bar
 */
Drupal.behaviors.tour = {
  attach: function (context) {
    $('body').once('tour', function (index, element) {
      var model = new Drupal.tour.models.StateModel();
      new Drupal.tour.views.ToggleTourView({
        el: $(context).find('#toolbar-tab-tour'),
        model: model
      });

      // Update the model based on Overlay events.
      $(document)
        // Overlay is opening: cancel tour if active and mark overlay as open.
        .on('drupalOverlayOpen.tour', function () {
          model.set({ isActive: false, overlayIsOpen: true });
        })
        // Overlay is loading a new URL: clear tour & cancel if active.
        .on('drupalOverlayBeforeLoad.tour', function () {
          model.set({ isActive: false, overlayTour: [] });
        })
        // Overlay is closing: clear tour & cancel if active, mark overlay closed.
        .on('drupalOverlayClose.tour', function () {
          model.set({ isActive: false, overlayIsOpen: false, overlayTour: [] });
        })
        // Overlay has loaded DOM: check whether a tour is available.
        .on('drupalOverlayReady.tour', function () {
          // We must select the tour in the Overlay's window using the Overlay's
          // jQuery, because the joyride plugin only works for the window in which
          // it was loaded.
          // @todo Make upstream contribution so this can be simplified, which
          // should also allow us to *not* load jquery.joyride.js in the Overlay,
          // resulting in better front-end performance.
          var overlay = Drupal.overlay.iframeWindow;
          var $overlayContext = overlay.jQuery(overlay.document);
          model.set('overlayTour', $overlayContext.find('ol#tour'));
        });

      model
        // Allow other scripts to respond to tour events.
        .on('change:isActive', function (model, isActive) {
          $(document).trigger((isActive) ? 'drupalTourStarted' : 'drupalTourStopped');
        })
        // Initialization: check whether a tour is available on the current page.
        .set('tour', $(context).find('ol#tour'));

      // Start the tour imediately if toggled via query string.
      var query = $.deparam.querystring();
      if (query.tour) {
        model.set('isActive', true);
      }

    });
  }
};

Drupal.tour = Drupal.tour || { models: {}, views: {}};

/**
 * Backbone Model for tours.
 */
Drupal.tour.models.StateModel = Backbone.Model.extend({
  defaults: {
    // Indicates whether the Drupal root window has a tour.
    tour: [],
    // Indicates whether the Overlay is open.
    overlayIsOpen: false,
    // Indicates whether the Overlay window has a tour.
    overlayTour: [],
    // Indicates whether the tour is currently running.
    isActive: false,
    // Indicates which tour is the active one (necessary to cleanly stop).
    activeTour: []
  }
});

/**
 * Handles edit mode toggle interactions.
 */
Drupal.tour.views.ToggleTourView = Backbone.View.extend({

  events: { 'click': 'onClick' },

  /**
   * Implements Backbone Views' initialize().
   */
  initialize: function () {
    this.model.on('change:tour change:overlayTour change:overlayIsOpen change:isActive', this.render, this);
    this.model.on('change:isActive', this.toggleTour, this);
  },

  /**
   * Implements Backbone Views' render().
   */
  render: function () {
    // Render the visibility.
    this.$el.toggleClass('hidden', this._getTour().length === 0);
    // Render the state.
    var isActive = this.model.get('isActive');
    this.$el.find('button')
      .toggleClass('active', isActive)
      .prop('aria-pressed', isActive);
    return this;
  },

  /**
   * Model change handler; starts or stops the tour.
   */
  toggleTour: function() {
    if (this.model.get('isActive')) {
      var $tour = this._getTour();
      this._removeIrrelevantTourItems($tour, this._getDocument());
      var that = this;
      if ($tour.find('li').length) {
        $tour.joyride({
          postRideCallback: function () { that.model.set('isActive', false); }
        });
        this.model.set({ isActive: true, activeTour: $tour });
      }
    }
    else {
      this.model.get('activeTour').joyride('destroy');
      this.model.set({ isActive: false, activeTour: [] });
    }
  },

  /**
   * Toolbar tab click event handler; toggles isActive.
   */
  onClick: function (event) {
    this.model.set('isActive', !this.model.get('isActive'));
    event.preventDefault();
    event.stopPropagation();
  },

  /**
   * Gets the tour.
   *
   * @return jQuery
   *   A jQuery element pointing to a <ol> containing tour items.
   */
  _getTour: function () {
    var whichTour = (this.model.get('overlayIsOpen')) ? 'overlayTour' : 'tour';
    return this.model.get(whichTour);
  },

  /**
   * Gets the relevant document as a jQuery element.
   *
   * @return jQuery
   *   A jQuery element pointing to the document within which a tour would be
   *   started given the current state. I.e. when the Overlay is open, this will
   *   point to the HTML document inside the Overlay's iframe, otherwise it will
   *   point to the Drupal root window.
   */
  _getDocument: function () {
    return (this.model.get('overlayIsOpen')) ? $(Drupal.overlay.iframeWindow.document) : $(document);
  },

  /**
   * Removes tour items for elements that don't have matching page elements or
   * are explicitly filtered out via the 'tips' query string.
   *
   * Example:
   *   http://example.com/foo?tips=bar
   *
   *   The above will filter out tips that do not have a matching page element or
   *   don't have the "bar" class.
   *
   * @param jQuery $tour
   *   A jQuery element pointing to a <ol> containing tour items.
   * @param jQuery $document
   *   A jQuery element pointing to the document within which the elements
   *   should be sought.
   *
   * @see _getDocument()
   */
  _removeIrrelevantTourItems: function ($tour, $document) {
    var removals = false;
    var query = $.deparam.querystring();
    $tour
      .find('li')
      .each(function () {
        var $this = $(this);
        var itemId = $this.attr('data-id');
        var itemClass = $this.attr('data-class');
        // If the query parameter 'tips' is set, remove all tips that don't
        // have the matching class.
        if (query.tips && !$(this).hasClass(query.tips)) {
          removals = true;
          $this.remove();
          return;
        }
        // Remove tip from the DOM if there is no corresponding page element.
        if ((!itemId && !itemClass) ||
            (itemId && $document.find('#' + itemId).length) ||
           (itemClass && $document.find('.' + itemClass).length)){
          return;
        }
        removals = true;
        $this.remove();
      });

    // If there were removals, we'll have to do some clean-up.
    if (removals) {
      var total = $tour.find('li').length;
      if (!total) {
        this.model.set({ tour: [] });
      }

      $tour
        .find('li')
        // Rebuild the progress data.
        .each(function (index) {
          var progress = Drupal.t('!tour_item of !total', { '!tour_item': index + 1, '!total': total });
          $(this).find('.tour-progress').text(progress);
        })
        // Update the last item to have "End tour" as the button.
        .last()
        .attr('data-text', Drupal.t('End tour'));
    }
  }

});

})(jQuery, Backbone, Drupal, document);
