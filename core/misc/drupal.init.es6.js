// Allow other JavaScript libraries to use $.
if (window.jQuery) {
  jQuery.noConflict();
}

// Class indicating that JS is enabled; used for styling purpose.
document.documentElement.className += ' js';

// JavaScript should be made compatible with libraries other than jQuery by
// wrapping it in an anonymous closure.

(function (domready, Drupal, drupalSettings) {
  // Attach all behaviors.
  domready(() => {
    Drupal.attachBehaviors(document, drupalSettings);
  });
}(domready, Drupal, window.drupalSettings));
