// $Id: displace.js,v 1.2 2010/05/14 16:44:37 dries Exp $
(function ($) {

/**
 * Provides a generic method to position elements fixed to the viewport.
 *
 * Fixed positioning (CSS declaration position:fixed) is done relative to the
 * viewport. This makes it hard to position multiple fixed positioned element
 * relative to each other (e.g. multiple toolbars should come after each other,
 * not on top of each other).
 *
 * To position an element fixed at the top of the viewport add the class
 * "displace-top" to that element, and to position it to the bottom of the view-
 * port add the class "displace-bottom".
 *
 * When a browser doesn't support position:fixed (like IE6) the element gets
 * positioned absolutely by default, but this can be overridden by using the
 * "displace-unsupported" class.
 */

/**
 * Attaches the displace behavior.
 */
Drupal.behaviors.displace = {
  attach: function (context, settings) {
    // Test for position:fixed support.
    if (!Drupal.positionFixedSupported()) {
      $(document.documentElement).addClass('displace-unsupported');
    }

    $(document.body).once('displace', function () {
      $(window).bind('resize.drupal-displace', function () {
        Drupal.displace.clearCache();

        $(document.body).css({
          paddingTop: Drupal.displace.getDisplacement('top'),
          paddingBottom: Drupal.displace.getDisplacement('bottom')
        });
      });
    });

    Drupal.displace.clearCache(true);
    $(window).triggerHandler('resize');
  }
};

/**
 * The displace object.
 */
Drupal.displace = Drupal.displace || {};

Drupal.displace.elements = [];
Drupal.displace.displacement = [];

/**
 * Get all displaced elements of given region.
 *
 * @param region
 *   Region name. Either "top" or "bottom".
 *
 * @return
 *   jQuery object containing all displaced elements of given region.
 */
Drupal.displace.getDisplacedElements = function (region) {
  if (!this.elements[region]) {
    this.elements[region] = $('.displace-' + region);
  }
  return this.elements[region];
};

/**
 * Get the total displacement of given region.
 *
 * @param region
 *   Region name. Either "top" or "bottom".
 *
 * @return
 *   The total displacement of given region in pixels.
 */
Drupal.displace.getDisplacement = function (region) {
  if (!this.displacement[region]) {
    var offset = 0;
    var height = 0;
    this.getDisplacedElements(region).each(function () {
      offset = offset + height;
      height = $(this).css(region, offset).outerHeight();

      // In IE, Shadow filter adds some extra height, so we need to remove it
      // from the returned height.
      if (this.filters && this.filters.length && this.filters.item('DXImageTransform.Microsoft.Shadow')) {
        height -= this.filters.item('DXImageTransform.Microsoft.Shadow').strength;
      }
    });

    // Use offset of latest displaced element as the total displacement.
    this.displacement[region] = offset + height;
  }

  return this.displacement[region];
};

/**
 * Clear cache.
 *
 * @param selectorCache
 *   Boolean whether to also clear the selector cache.
 */
Drupal.displace.clearCache = function (selectorCache) {
  if (selectorCache) {
    this.elements = [];
  }
  this.displacement = [];
};

})(jQuery);
