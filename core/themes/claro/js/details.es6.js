/**
 * @file
 * Claro's polyfill enhancements for HTML5 details.
 */

(($, Modernizr, Drupal) => {
  /**
   * Workaround for Firefox.
   *
   * Firefox applies the focus state only for keyboard navigation.
   * We have to manually trigger focus to make the behavior consistent across
   * browsers.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.claroDetails = {
    attach(context) {
      $(context)
        .once('claroDetails')
        .on('click', event => {
          if (event.target.nodeName === 'SUMMARY') {
            $(event.target).trigger('focus');
          }
        });
    },
  };

  /**
   * Workaround for non-supporting browsers.
   *
   * This shim extends HTML5 Shiv used by core.
   *
   * HTML5 Shiv toggles focused details for hitting enter. We copy that for
   * space key as well to make the behavior consistent across browsers.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.claroDetailsToggleShim = {
    attach(context) {
      if (Modernizr.details || !Drupal.CollapsibleDetails.instances.length) {
        return;
      }

      $(context)
        .find('details .details-title')
        .once('claroDetailsToggleShim')
        .on('keypress', event => {
          const keyCode = event.keyCode || event.charCode;
          if (keyCode === 32) {
            $(event.target)
              .closest('summary')
              .trigger('click');
            event.preventDefault();
          }
        });
    },
  };
})(jQuery, Modernizr, Drupal);
