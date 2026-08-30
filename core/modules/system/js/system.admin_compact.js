/**
 * @file
 * Defines the behavior of admin compact mode.
 *
 * Collapses certain elements on admin pages to make them more compact.
 */

(function (Drupal, once) {
  // Set UI-impacting admin-compact-mode data attribute before Drupal behaviors
  // initialize to minimize flickering on load.
  if (JSON.parse(localStorage.getItem('Drupal.admin.compact_mode')) === true) {
    document.documentElement.setAttribute('data-admin-compact-mode', '');
  }

  /**
   * Toggles admin compact mode.
   *
   * @return {boolean}
   *   The next state: true if contact mode is enabled, false otherwise.
   */
  function toggleCompactMode() {
    const nextState = !document.documentElement.hasAttribute(
      'data-admin-compact-mode',
    );
    document.documentElement.toggleAttribute(
      'data-admin-compact-mode',
      nextState,
    );
    localStorage.setItem(
      'Drupal.admin.compact_mode',
      nextState ? 'true' : 'false',
    );

    return nextState;
  }

  /**
   * Update the toggle link element.
   *
   * @param {HTMLElement} element
   *   The toggle link element.
   * @param {boolean} compactMode
   *   The current state: true if contact mode is enabled, false otherwise.
   */
  function updateToggleElement(element, compactMode) {
    element.innerText = compactMode
      ? Drupal.t('Show descriptions')
      : Drupal.t('Hide descriptions');
    element.title = compactMode
      ? Drupal.t('Expand layout to include descriptions.')
      : Drupal.t('Compress layout by hiding descriptions.');
  }

  /**
   * Adds a click event listener to any [data-admin-compact-toggle] elements.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.adminCompactMode = {
    attach(context) {
      once(
        'admin-compact-mode',
        '[data-admin-compact-toggle]',
        context,
      ).forEach((toggle) => {
        const initialMode = document.documentElement.hasAttribute(
          'data-admin-compact-mode',
        );
        updateToggleElement(toggle, initialMode);
        toggle.addEventListener('click', (event) => {
          const compactMode = toggleCompactMode();
          updateToggleElement(toggle, compactMode);
          event.preventDefault();
        });
      });
    },
  };
})(Drupal, once);
