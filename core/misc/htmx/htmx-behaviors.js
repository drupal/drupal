/**
 * @file
 * Connect Drupal.behaviors to htmx inserted content.
 */
(function (Drupal, htmx, drupalSettings) {
  // Flag used to prevent running htmx initialization twice on elements we know
  // have already been processed.
  let attachFromHtmx = false;

  // This is a custom event that triggers once the htmx request settled and
  // all JS and CSS assets have been loaded successfully.
  // @see https://htmx.org/api/#on
  // @see htmx-assets.js
  htmx.on('htmx:drupal:load', ({ detail }) => {
    attachFromHtmx = true;
    Drupal.attachBehaviors(detail.elt, drupalSettings);
    attachFromHtmx = false;
  });

  // When htmx removes elements from the DOM, make sure they're detached first.
  // This event is currently an alias of htmx:beforeSwap
  htmx.on('htmx:drupal:unload', ({ detail }) => {
    Drupal.detachBehaviors(detail.elt, drupalSettings, 'unload');
  });

  /**
   * Initialize HTMX library on content added by Drupal Ajax Framework.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Initialize htmx behavior.
   */
  Drupal.behaviors.htmx = {
    attach(context) {
      if (!attachFromHtmx && context !== document) {
        htmx.process(context);
      }
    },
  };
})(Drupal, htmx, drupalSettings);
