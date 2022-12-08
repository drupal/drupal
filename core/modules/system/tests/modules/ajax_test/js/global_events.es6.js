/**
 * @file
 * For testing that jQuery's ajaxSuccess, ajaxComplete, and ajaxStop events
 * are triggered only after commands in a Drupal Ajax response are executed.
 */

(($, Drupal) => {
  ['ajaxSuccess', 'ajaxComplete', 'ajaxStop'].forEach((eventName) => {
    $(document)[eventName](() => {
      $('#test_global_events_log').append(eventName);
      $('#test_global_events_log2').append(eventName);
    });
  });
})(jQuery, Drupal);
