/**
 * Marks the nodes listed in drupalSettings.history.nodesToMarkAsRead as read.
 *
 * Uses the History module JavaScript API.
 */
(function (window, Drupal, drupalSettings) {

  "use strict";

  // When the window's "load" event is triggered, mark all enumerated nodes as
  // read. This still allows for Drupal behaviors (which are triggered on the
  // "DOMContentReady" event) to add "new" and "updated" indicators.
  window.addEventListener('load', function() {
    if (drupalSettings.history && drupalSettings.history.nodesToMarkAsRead) {
      Object.keys(drupalSettings.history.nodesToMarkAsRead).forEach(Drupal.history.markAsRead);
    }
  });

})(window, Drupal, drupalSettings);
