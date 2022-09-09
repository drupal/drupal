/**
 * @file
 *  Testing behavior for JSInteractionTest.
 */

(({ behaviors }) => {
  /**
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the click listener on the trigger link.
   */
  behaviors.js_interaction_test_trigger_link = {
    attach() {
      const removeBlockerTrigger = once(
        'remove-blocker-trigger',
        '.remove-blocker-trigger',
      ).shift();
      removeBlockerTrigger.addEventListener('click', (event) => {
        event.preventDefault();
        setTimeout(() => {
          document.querySelector('.blocker-element').remove();
        }, 100);
      });

      const enableFieldTrigger = once(
        'enable-field-trigger',
        '.enable-field-trigger',
      ).shift();
      enableFieldTrigger.addEventListener('click', (event) => {
        event.preventDefault();
        setTimeout(() => {
          document.querySelector('input[name="target_field"]').disabled = false;
        }, 100);
      });
    },
  };
})(Drupal);
