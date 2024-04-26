/* cspell:ignore uidom */
((Drupal, once, { computePosition, offset, shift, flip }) => {
  /**
   * Attaches the dropdown behavior to all required triggers.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the dropdown behavior.
   */
  Drupal.behaviors.dropdownInit = {
    attach: (context) => {
      once('dropdown-trigger', '[data-drupal-dropdown]', context).forEach(
        (trigger) => {
          const dropdown = trigger.nextElementSibling;

          const updatePosition = () => {
            computePosition(trigger, dropdown, {
              strategy: 'fixed',
              placement: trigger.dataset.drupalDropdownPosition || 'bottom',
              middleware: [
                flip({ padding: 16 }),
                offset(6),
                shift({ padding: 16 }),
              ],
            }).then(({ x, y }) => {
              Object.assign(dropdown.style, {
                left: `${x}px`,
                top: `${y}px`,
              });
            });
          };

          trigger.addEventListener('click', (e) => {
            updatePosition();
            trigger.setAttribute(
              'aria-expanded',
              e.currentTarget.getAttribute('aria-expanded') === 'false',
            );
          });

          // Event listener to close dropdown when clicking outside
          document.addEventListener('click', (e) => {
            const isButtonClicked = trigger.contains(e.target);
            if (!isButtonClicked) {
              trigger.setAttribute('aria-expanded', 'false');
            }
          });
        },
      );
    },
  };
})(Drupal, once, FloatingUIDOM);
