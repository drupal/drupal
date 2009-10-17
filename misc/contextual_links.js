// $Id$
(function ($) {

Drupal.contextualLinks = Drupal.contextualLinks || {};

/**
 * Attach outline behavior for regions associated with contextual links.
 */
Drupal.behaviors.contextualLinks = {
  attach: function (context) {
    $('ul.contextual-links', context).once('contextual-links', function () {
      $(this).hover(Drupal.contextualLinks.hover, Drupal.contextualLinks.hoverOut);
    });
  }
};

/**
 * Enables outline for the region contextual links are associated with.
 */
Drupal.contextualLinks.hover = function () {
  $(this).addClass('contextual-links-link-active')
    .closest('.contextual-links-region').addClass('contextual-links-region-active');
};

/**
 * Disables outline for the region contextual links are associated with.
 */
Drupal.contextualLinks.hoverOut = function () {
  $(this).removeClass('contextual-links-link-active')
    .closest('.contextual-links-region').removeClass('contextual-links-region-active');
};

})(jQuery);
