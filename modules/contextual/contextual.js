// $Id: contextual.js,v 1.4 2009/12/14 10:38:19 dries Exp $
(function ($) {

Drupal.contextualLinks = Drupal.contextualLinks || {};

/**
 * Attach outline behavior for regions associated with contextual links.
 */
Drupal.behaviors.contextualLinks = {
  attach: function (context) {
    $('div.contextual-links-wrapper', context).once('contextual-links', function () {
      var $wrapper = $(this);
      var $region = $wrapper.closest('.contextual-links-region');
      var $links = $wrapper.find('ul.contextual-links');
      var $trigger = $('<a class="contextual-links-trigger" href="#" />').text(Drupal.t('Configure')).click(
        function () {
          $wrapper.find('ul.contextual-links').stop(true, true).slideToggle(100);
          $wrapper.toggleClass('contextual-links-active');
          return false;
        }
      );
      // Attach hover behavior to trigger and ul.contextual-links.
      $trigger.add($links).hover(
        function () { $region.addClass('contextual-links-region-active'); },
        function () { $region.removeClass('contextual-links-region-active'); }
      );
      // Hide the contextual links when user rolls out of the .contextual-links-region.
      $region.bind('mouseleave', Drupal.contextualLinks.mouseleave);
      // Prepend the trigger.
      $links.end().prepend($trigger);
    });
  }
};

/**
 * Disables outline for the region contextual links are associated with.
 */
Drupal.contextualLinks.mouseleave = function () {
  $(this).closest('.contextual-links-region')
    .find('.contextual-links-active').removeClass('contextual-links-active')
    .find('ul.contextual-links').hide();
};

})(jQuery);
