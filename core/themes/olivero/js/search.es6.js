/**
 * @file
 * Customization of search.
 */

((Drupal) => {
  const searchWideButton = document.querySelector(
    '[data-drupal-selector="block-search-wide-button"]',
  );
  const searchWideWrapper = document.querySelector(
    '[data-drupal-selector="block-search-wide-wrapper"]',
  );

  /**
   * Determine if search is visible.
   *
   * @return {boolean}
   *   True if the search wrapper contains "is-active" class, false if not.
   */
  function searchIsVisible() {
    return searchWideWrapper.classList.contains('is-active');
  }
  Drupal.olivero.searchIsVisible = searchIsVisible;

  /**
   * Set focus for the search input element.
   */
  function handleFocus() {
    if (searchIsVisible()) {
      searchWideWrapper.querySelector('input[type="search"]').focus();
    } else {
      searchWideButton.focus();
    }
  }

  /**
   * Toggle search functionality visibility.
   *
   * @param {boolean} visibility
   *   True if we want to show the form, false if we want to hide it.
   */
  function toggleSearchVisibility(visibility) {
    searchWideButton.setAttribute('aria-expanded', visibility === true);
    searchWideWrapper.addEventListener('transitionend', handleFocus, {
      once: true,
    });

    if (visibility === true) {
      Drupal.olivero.closeAllSubNav();
      searchWideWrapper.classList.add('is-active');
    } else {
      searchWideWrapper.classList.remove('is-active');
    }
  }

  Drupal.olivero.toggleSearchVisibility = toggleSearchVisibility;

  document.addEventListener('keyup', (e) => {
    if (e.key === 'Escape' || e.key === 'Esc') {
      toggleSearchVisibility(false);
    }
  });

  document.addEventListener('click', (e) => {
    if (
      e.target.matches(
        '[data-drupal-selector="block-search-wide-button"], [data-drupal-selector="block-search-wide-button"] *',
      )
    ) {
      toggleSearchVisibility(!searchIsVisible());
    } else if (
      searchIsVisible() &&
      !e.target.matches(
        '[data-drupal-selector="block-search-wide-wrapper"], [data-drupal-selector="block-search-wide-wrapper"] *',
      )
    ) {
      toggleSearchVisibility(false);
    }
  });
})(Drupal);
