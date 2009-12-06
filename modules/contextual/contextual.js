// $Id$
(function ($) {

Drupal.contextualLinks = Drupal.contextualLinks || {};

/**
 * Attach outline behavior for regions associated with contextual links.
 */
Drupal.behaviors.contextualLinks = {
  attach: function (context) {
    $('div.contextual-links-wrapper', context).once('contextual-links', function () {
      var $wrapper = $(this);
      var $trigger = $('<a class="contextual-links-trigger" href="#" />').text(Drupal.t('Configure')).click(
        function () {
          $wrapper.find('ul.contextual-links').slideToggle(100);
          $wrapper.toggleClass('contextual-links-active');
          return false;
        }
      );
      $wrapper.prepend($trigger)
        .closest('.contextual-links-region').hover(Drupal.contextualLinks.hover, Drupal.contextualLinks.hoverOut);
    });
  }
};

/**
 * Enables outline for the region contextual links are associated with.
 */
Drupal.contextualLinks.hover = function () {
  $(this).closest('.contextual-links-region').addClass('contextual-links-region-active');
};

/**
 * Disables outline for the region contextual links are associated with.
 */
Drupal.contextualLinks.hoverOut = function () {
  $(this).closest('.contextual-links-region').removeClass('contextual-links-region-active')
    .find('.contextual-links-active').removeClass('contextual-links-active')
    .find('ul.contextual-links').hide();
};

})(jQuery);
