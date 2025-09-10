/**
 * @file
 *  Support code for testing AJAX requests in functional tests.
 */
window.drupalCumulativeXhrCount = 0;
(function ($, htmx) {
  function increment() {
    window.drupalCumulativeXhrCount++;
    window.drupalActiveXhrCount = window.drupalActiveXhrCount
      ? window.drupalActiveXhrCount + 1
      : 1;
  }
  function decrement() {
    window.drupalActiveXhrCount--;
  }
  // jQuery.active alone is unable to detect whether an XHR request ever occurred.
  /* eslint-disable no-jquery/no-ajax-events */
  $(document).on('ajaxSend', increment).on('ajaxComplete', decrement);
  if (htmx) {
    htmx.on('htmx:beforeSend', increment);
    htmx.on('htmx:afterRequest', decrement);
  }
})(jQuery, window.htmx);
