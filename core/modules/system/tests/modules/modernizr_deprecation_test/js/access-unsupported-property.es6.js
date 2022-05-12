/**
 * @file
 * For testing a Modernizr deprecated property.
 */

((Drupal, once, Modernizr) => {
  Drupal.behaviors.unsupportedModernizrProperty = {
    attach() {
      once('unsupported-modernizr-property', 'body').forEach(() => {
        const triggerDeprecationButton = document.createElement('button');
        triggerDeprecationButton.id = 'trigger-a-deprecation';
        triggerDeprecationButton.textContent = 'trigger a deprecation';
        triggerDeprecationButton.addEventListener('click', () => {
          // eslint-disable-next-line no-unused-vars
          const thisShouldTriggerWarning = Modernizr.touchevents;
        });
        document.querySelector('main').append(triggerDeprecationButton);
      });
    },
  };
})(Drupal, once, Modernizr);
