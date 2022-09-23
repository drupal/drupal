/**
 * @file
 * Wide viewport search bar interactions.
 */

((Drupal) => {
  const searchWideButtonSelector =
    '[data-drupal-selector="block-search-wide-button"]';
  const searchWideButton = document.querySelector(searchWideButtonSelector);
  const searchWideWrapperSelector =
    '[data-drupal-selector="block-search-wide-wrapper"]';
  const searchWideWrapper = document.querySelector(searchWideWrapperSelector);

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
   * Closes search bar when a click event does not happen at an (x,y) coordinate
   * that does not overlap with either the search wrapper or button.
   *
   * @see https://bugs.webkit.org/show_bug.cgi?id=229895
   *
   * @param {Event} e click event
   */
  function watchForClickOut(e) {
    const clickInSearchArea = e.target.matches(`
      ${searchWideWrapperSelector},
      ${searchWideWrapperSelector} *,
      ${searchWideButtonSelector},
      ${searchWideButtonSelector} *
    `);
    if (!clickInSearchArea && searchIsVisible()) {
      // eslint-disable-next-line no-use-before-define
      toggleSearchVisibility(false);
    }
  }

  /**
   * Closes search bar when focus moves to another target.
   * Avoids closing search bar if event does not have related target - required for Safari.
   *
   * @see https://bugs.webkit.org/show_bug.cgi?id=229895
   *
   * @param {Event} e focusout event
   */
  function watchForFocusOut(e) {
    if (e.relatedTarget) {
      const inSearchBar = e.relatedTarget.matches(
        `${searchWideWrapperSelector}, ${searchWideWrapperSelector} *`,
      );
      const inSearchButton = e.relatedTarget.matches(
        `${searchWideButtonSelector}, ${searchWideButtonSelector} *`,
      );

      if (!inSearchBar && !inSearchButton) {
        // eslint-disable-next-line no-use-before-define
        toggleSearchVisibility(false);
      }
    }
  }

  /**
   * Closes search bar on escape keyup, if open.
   *
   * @param {Event} e keyup event
   */
  function watchForEscapeOut(e) {
    if (e.key === 'Escape') {
      // eslint-disable-next-line no-use-before-define
      toggleSearchVisibility(false);
    }
  }

  /**
   * Set focus for the search input element.
   */
  function handleFocus() {
    if (searchIsVisible()) {
      searchWideWrapper.querySelector('input[type="search"]').focus();
    } else if (searchWideWrapper.contains(document.activeElement)) {
      // Return focus to button only if focus was inside of the search wrapper.
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
    searchWideWrapper.classList.toggle('is-active', visibility === true);
    searchWideWrapper.addEventListener('transitionend', handleFocus, {
      once: true,
    });

    if (visibility === true) {
      Drupal.olivero.closeAllSubNav();

      document.addEventListener('click', watchForClickOut, { capture: true });
      document.addEventListener('focusout', watchForFocusOut, {
        capture: true,
      });
      document.addEventListener('keyup', watchForEscapeOut, { capture: true });
    } else {
      document.removeEventListener('click', watchForClickOut, {
        capture: true,
      });
      document.removeEventListener('focusout', watchForFocusOut, {
        capture: true,
      });
      document.removeEventListener('keyup', watchForEscapeOut, {
        capture: true,
      });
    }
  }

  Drupal.olivero.toggleSearchVisibility = toggleSearchVisibility;

  /**
   * Initializes the search wide button.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *  Adds aria-expanded attribute to the search wide button.
   */
  Drupal.behaviors.searchWide = {
    attach(context) {
      const searchWideButtonEl = once(
        'search-wide',
        searchWideButtonSelector,
        context,
      ).shift();
      if (searchWideButtonEl) {
        searchWideButtonEl.setAttribute('aria-expanded', searchIsVisible());
        searchWideButtonEl.addEventListener('click', () => {
          toggleSearchVisibility(!searchIsVisible());
        });
      }
    },
  };
})(Drupal);
