// $Id: dropdown.js,v 1.6 2010/10/11 22:18:22 sdboyer Exp $
/**
 * @file
 * Implement a simple, clickable dropdown menu.
 *
 * See dropdown.theme.inc for primary documentation.
 *
 * The javascript relies on four classes:
 * - The dropdown must be fully contained in a div with the class
 *   ctools-dropdown. It must also contain the class ctools-dropdown-no-js
 *   which will be immediately removed by the javascript; this allows for
 *   graceful degradation.
 * - The trigger that opens the dropdown must be an a tag wit hthe class
 *   ctools-dropdown-link. The href should just be '#' as this will never
 *   be allowed to complete.
 * - The part of the dropdown that will appear when the link is clicked must
 *   be a div with class ctools-dropdown-container.
 * - Finally, ctools-dropdown-hover will be placed on any link that is being
 *   hovered over, so that the browser can restyle the links.
 *
 * This tool isn't meant to replace click-tips or anything, it is specifically
 * meant to work well presenting menus.
 */

(function ($) {
  Drupal.behaviors.CToolsDropdown = {
    attach: function() {
      $('div.ctools-dropdown:not(.ctools-dropdown-processed)')
        .removeClass('ctools-dropdown-no-js')
        .addClass('ctools-dropdown-processed')
        .each(function() {
          var $dropdown = $(this);
          var open = false;
          var hovering = false;
          var timerID = 0;

          var toggle = function(close) {
            // if it's open or we're told to close it, close it.
            if (open || close) {
              // If we're just toggling it, close it immediately.
              if (!close) {
                open = false;
                $("div.ctools-dropdown-container", $dropdown).slideUp(100);
              }
              else {
                // If we were told to close it, wait half a second to make
                // sure that's what the user wanted.
                // Clear any previous timer we were using.
                if (timerID) {
                  clearTimeout(timerID);
                }
                timerID = setTimeout(function() {
                  if (!hovering) {
                    open = false;
                    $("div.ctools-dropdown-container", $dropdown).slideUp(100);
                  }}, 500);
              }
            }
            else {
              // open it.
              open = true;
              $("div.ctools-dropdown-container", $dropdown)
                .animate({height: "show", opacity: "show"}, 100);
            }
          }
          $("a.ctools-dropdown-link", $dropdown).click(function() {
              toggle();
              return false;
            });

          $dropdown.hover(
            function() {
              hovering = true;
            }, // hover in
            function() { // hover out
              hovering = false;
              toggle(true);
              return false;
            });
            // @todo -- just use CSS for this noise.
          $("div.ctools-dropdown-container a").hover(
            function() { $(this).addClass('ctools-dropdown-hover'); },
            function() { $(this).removeClass('ctools-dropdown-hover'); }
            );
        });
    }
  }
})(jQuery);
