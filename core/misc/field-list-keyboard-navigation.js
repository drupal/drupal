/**
 * @file
 * Attaches behaviors for Drupal's field list keyboard navigation.
 */
(function (Drupal, { isFocusable }) {
  /**
   * Attaches the focus shifting functionality.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches behaviors.
   */
  Drupal.behaviors.fieldListKeyboardNavigation = {
    attach() {
      once(
        'keyboardNavigation',
        '[data-field-list-table] :is(input[type="text"], input[type="number"])',
      ).forEach((element) =>
        element.addEventListener('keypress', (event) => {
          if (event.key !== 'Enter') {
            return;
          }
          event.preventDefault();
          const currentElement = event.target;

          // Function to find the next focusable element.
          const findNextFocusableElement = (element) => {
            const currentRow = element.closest('tr');
            const inputElements = currentRow.querySelectorAll(
              'input[type="text"], input[type="number"]',
            );
            const afterIndex = [...inputElements].indexOf(element) + 1;

            // eslint-disable-next-line no-restricted-syntax
            for (const inputElement of [...inputElements].slice(afterIndex)) {
              if (isFocusable(inputElement)) {
                return inputElement;
              }
            }
            const nextRow = currentRow.nextElementSibling;
            if (nextRow) {
              return findNextFocusableElement(nextRow);
            }
            return null;
          };

          const nextFocusableElement = findNextFocusableElement(currentElement);

          // If a focusable element is found, move focus there.
          if (nextFocusableElement) {
            nextFocusableElement.focus();
            // Move cursor to the end of the input.
            const value = nextFocusableElement.value;
            nextFocusableElement.value = '';
            nextFocusableElement.value = value;
            return;
          }
          // If no focusable element is found, add another item to the list.
          event.target
            .closest('[data-field-list-table]')
            .parentNode.querySelector('[data-field-list-button]')
            .dispatchEvent(new Event('mousedown'));
        }),
      );
    },
  };
})(Drupal, window.tabbable);
