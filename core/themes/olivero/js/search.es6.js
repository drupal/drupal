((Drupal) => {
  const searchWideButton = document.querySelector(
    '[data-drupal-selector="block-search-wide-button"]',
  );
  const searchWideWrapper = document.querySelector(
    '[data-drupal-selector="block-search-wide-wrapper"]',
  );

  function searchIsVisible() {
    return searchWideWrapper.classList.contains('is-active');
  }
  Drupal.olivero.searchIsVisible = searchIsVisible;

  function handleFocus() {
    if (searchIsVisible()) {
      searchWideWrapper.querySelector('input[type="search"]').focus();
    } else {
      searchWideButton.focus();
    }
  }

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
