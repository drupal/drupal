((Drupal, once, tabbable) => {
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

    // Focus trap. This is added to the header element because the navButton
    // element is not a child element of the navWrapper element, and the keydown
    // event would not fire if focus is on the navButton element.
    props.header.addEventListener('keydown', (e) => {
      if (e.key === 'Tab' && isNavOpen(props.navWrapper)) {
        const tabbableNavElements = tabbable.tabbable(props.navWrapper);
        tabbableNavElements.unshift(props.navButton);
        const firstTabbableEl = tabbableNavElements[0];
        const lastTabbableEl =
          tabbableNavElements[tabbableNavElements.length - 1];

        if (e.shiftKey) {
          if (
            document.activeElement === firstTabbableEl &&
            !props.olivero.isDesktopNav()
          ) {
            lastTabbableEl.focus();
            e.preventDefault();
          }
        } else if (
          document.activeElement === lastTabbableEl &&
          !props.olivero.isDesktopNav()
        ) {
          firstTabbableEl.focus();
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
    attach(context) {
      const headerId = 'header';
      const header = once(
        'olivero-navigation',
        `#${headerId}`,
        context,
      ).shift();
      const navWrapperId = 'header-nav';

      if (header) {
        const navWrapper = header.querySelector('#header-nav');
        const { olivero } = Drupal;
        const navButton = context.querySelector('.mobile-nav-button');
        const body = context.querySelector('body');
        const overlay = context.querySelector('.overlay');

        init({
          olivero,
          header,
          navWrapperId,
          navWrapper,
          navButton,
          body,
          overlay,
        });
      }
    },
  };
})(Drupal, once, tabbable);
