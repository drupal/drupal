/**
 * @file
 * For testing FocusFirstCommand.
 */

((Drupal) => {
  Drupal.behaviors.focusFirstTest = {
    attach() {
      // Add data-has-focus attribute to focused elements so tests have a
      // selector to wait for before moving to the next test step.
      once('focusin', document.body).forEach((element) => {
        element.addEventListener('focusin', (e) => {
          document
            .querySelectorAll('[data-has-focus]')
            .forEach((wasFocused) => {
              wasFocused.removeAttribute('data-has-focus');
            });
          e.target.setAttribute('data-has-focus', true);
        });
      });
    },
  };
})(Drupal, once);
