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
      // The second argument of once() needs to be an instance of Element, but
      // document is an instance of Document, replace it with the html Element.
      $(once('claroDetails', context === document ? 'html' : context)).on(
        'click',
        (event) => {
          if (event.target.nodeName === 'SUMMARY') {
            $(event.target).trigger('focus');
          }
        },
      );
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

      $(once('claroDetailsToggleShim', 'details .details-title', context)).on(
        'keypress',
        (event) => {
          const keyCode = event.keyCode || event.charCode;
          if (keyCode === 32) {
            $(event.target).closest('summary').trigger('click');
            event.preventDefault();
          }
        },
      );
    },
  };

  /**
   * Theme override providing a wrapper for summarized details content.
   *
   * @return {string}
   *   The markup for the element that will contain the summarized content.
   */
  Drupal.theme.detailsSummarizedContentWrapper = () =>
    `<span class="claro-details__summary-summary"></span>`;

  /**
   * Theme override of summarized details content text.
   *
   * @param {string|null} [text]
   *   (optional) The summarized content displayed in the summary.
   * @return {string}
   *   The formatted summarized content text.
   */
  Drupal.theme.detailsSummarizedContentText = (text) => text || '';
})(jQuery, Modernizr, Drupal);
