((Drupal) => {
  /**
   * Checks if navWrapper contains "is-active" class.
   * @param {object} navWrapper
   *   Header navigation.
   * @return {boolean}
   *   True if navWrapper contains "is-active" class, false if not.
   */
  function isNavOpen(navWrapper) {
    return navWrapper.classList.contains('is-active');
  }

  /**
   * Opens or closes the header navigation.
   * @param {object} props
   *   Navigation props.
   * @param {boolean} state
   *   State which to transition the header navigation menu into.
   */
  function toggleNav(props, state) {
    const value = !!state;
    props.navButton.setAttribute('aria-expanded', value);

    if (value) {
      props.body.classList.add('js-overlay-active');
      props.body.classList.add('js-fixed');
      props.navWrapper.classList.add('is-active');
    } else {
      props.body.classList.remove('js-overlay-active');
      props.body.classList.remove('js-fixed');
      props.navWrapper.classList.remove('is-active');
    }
  }

  /**
   * Init function for header navigation.
   * @param {object} props
   *   Navigation props.
   */
  function init(props) {
    props.navButton.setAttribute('aria-controls', props.navWrapperId);
    props.navButton.setAttribute('aria-expanded', 'false');

    props.navButton.addEventListener('click', () => {
      toggleNav(props, !isNavOpen(props.navWrapper));
    });

    // Closes any open sub navigation first, then close header navigation.
    document.addEventListener('keyup', (e) => {
      if (e.key === 'Escape') {
        if (props.olivero.areAnySubNavsOpen()) {
          props.olivero.closeAllSubNav();
        } else {
          toggleNav(props, false);
        }
      }
    });

    props.overlay.addEventListener('click', () => {
      toggleNav(props, false);
    });

    props.overlay.addEventListener('touchstart', () => {
      toggleNav(props, false);
    });

    // Focus trap.
    props.navWrapper.addEventListener('keydown', (e) => {
      if (e.key === 'Tab') {
        if (e.shiftKey) {
          if (
            document.activeElement === props.firstFocusableEl &&
            !props.olivero.isDesktopNav()
          ) {
            props.navButton.focus();
            e.preventDefault();
          }
        } else if (
          document.activeElement === props.lastFocusableEl &&
          !props.olivero.isDesktopNav()
        ) {
          props.navButton.focus();
          e.preventDefault();
        }
      }
    });

    // Remove overlays when browser is resized and desktop nav appears.
    // @todo Use core/drupal.debounce library to throttle when we move into theming.
    window.addEventListener('resize', () => {
      if (props.olivero.isDesktopNav()) {
        toggleNav(props, false);
        props.body.classList.remove('js-overlay-active');
        props.body.classList.remove('js-fixed');
      }

      // Ensure that all sub-navigation menus close when the browser is resized.
      Drupal.olivero.closeAllSubNav();
    });
  }

  /**
   * Initialize the navigation JS.
   */
  Drupal.behaviors.oliveroNavigation = {
    attach(context, settings) {
      const navWrapperId = 'header-nav';
      const navWrapper = context.querySelector(
        `#${navWrapperId}:not(.${navWrapperId}-processed)`,
      );
      if (navWrapper) {
        navWrapper.classList.add(`${navWrapperId}-processed`);
        const { olivero } = Drupal;
        const navButton = context.querySelector('.mobile-nav-button');
        const body = context.querySelector('body');
        const overlay = context.querySelector('.overlay');
        const focusableNavElements = navWrapper.querySelectorAll(
          'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])',
        );
        const firstFocusableEl = focusableNavElements[0];
        const lastFocusableEl =
          focusableNavElements[focusableNavElements.length - 1];

        init({
          settings,
          olivero,
          navWrapperId,
          navWrapper,
          navButton,
          body,
          overlay,
          firstFocusableEl,
          lastFocusableEl,
        });
      }
    },
  };
})(Drupal);
