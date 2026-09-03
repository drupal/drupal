/**
 * @file
 *  Support code for testing AJAX requests in functional tests.
 */
window.drupalCumulativeXhrCount = 0;
(function ($) {
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
  // Catch calls to native fetch().
  const oldFetch = window.fetch;
  window.fetch = function newFetch(resource, options) {
    increment();

    return oldFetch
      .call(window, resource, options)
      .then(async (response) => {
        try {
          await response.clone().text();
          // Fetch normally "completes" when the header arrives, but the test
          // content won't be processed at that point. This occasionally creates
          // a race condition.  The line above ensures this promise won't
          // continue until every byte of the response body is received.
        } catch {
          // Ignore errors while reading the monitoring copy.
        }
        return response;
      })
      .finally(() => {
        // Decrement the active count even if the fetch() promise is rejected.
        decrement();
      });
  };
})(window.jQuery);
