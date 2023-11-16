/**
 * @file
 *  Support code for testing AJAX requests in functional tests.
 */
window.drupalCumulativeXhrCount = 0;
(function ($) {
  // jQuery.active alone is unable to detect whether an XHR request ever occurred.
  /* eslint-disable jquery/no-ajax-events */
  $(document)
    .on('ajaxSend', function () {
      window.drupalCumulativeXhrCount++;
      window.drupalActiveXhrCount = window.drupalActiveXhrCount
        ? window.drupalActiveXhrCount + 1
        : 1;
    })
    .on('ajaxComplete', function () {
      window.drupalActiveXhrCount--;
    });
})(jQuery);
