/**
 * @file
 * Fixes an IE11 bug where elements incorrectly receive focus.
 */

(($) => {
  /**
   * Fix for an IE11 bug that makes some items incorrectly focusable on click.
   *
   * IE11 has an issue where any item set to `display: flex` can be focused
   * by clicking on them, even if they shouldn't actually be focusable. This
   * issue is more noticeable in Claro due to it overriding focus styles with
   * a very visible focus ring.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.ie11focus = {
    attach(context) {
      // Check if the browser is Internet Explorer.
      if (
        navigator.userAgent.indexOf('MSIE') !== -1 ||
        navigator.appVersion.indexOf('Trident/') > -1
      ) {
        // Add a listener for all elements receiving mousedown.
        $(context)
          .find('*')
          .once('prevent-focus-errors')
          .on('mousedown', (e) => {
            const $target = $(e.target);
            // If the element receiving mousedown isn't focusable, it should
            // have no use for a click event anyway. Preventing default on
            // mousedown for these elements prevents the IE11 elements that
            // shouldn't be focusable from receiving focus.
            if (
              !$target.is(
                'button,input,select,textarea,a,audio,video,[tabindex],[contenteditable],iframe,embed[type="video/quicktime"],embed[type="video/mp4"],object[type="application/x-shockwave-flash"],svg:not([focusable="false"]),rect[focusable="true"]',
              )
            ) {
              e.preventDefault();
            }
          });
      }
    },
  };
})(jQuery);
