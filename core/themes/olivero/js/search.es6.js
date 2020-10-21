((Drupal) => {
  const searchWideButton = document.querySelector('.header-nav__search-button');
  const searchWideWrapper = document.querySelector('.search-wide__wrapper');

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
      searchWideWrapper.classList.add('is-active');
    } else {
      searchWideWrapper.classList.remove('is-active');
    }
  }

  Drupal.olivero.toggleSearchVisibility = toggleSearchVisibility;

  document.addEventListener('click', (e) => {
    if (
      e.target.matches(
        '.header-nav__search-button, .header-nav__search-button *',
      )
    ) {
      toggleSearchVisibility(!searchIsVisible());
    } else if (
      searchIsVisible() &&
      !e.target.matches('.search-wide__wrapper, .search-wide__wrapper *')
    ) {
      toggleSearchVisibility(false);
    }
  });
})(Drupal);
