((Drupal) => {
  const { isDesktopNav } = Drupal.olivero;
  const secondLevelNavMenus = document.querySelectorAll(
    '.primary-nav__menu-item--has-children',
  );

  /**
   * Shows and hides the specified menu item's second level submenu.
   *
   * @param {element} topLevelMenuITem - the <li> element that is the container for the menu and submenus.
   * @param {boolean} [toState] - Optional state where we want the submenu to end up.
   */
  function toggleSubNav(topLevelMenuITem, toState) {
    const buttonSelector =
      '.primary-nav__button-toggle, .primary-nav__menu-link--button';
    const button = topLevelMenuITem.querySelector(buttonSelector);
    const state =
      toState !== undefined
        ? toState
        : button.getAttribute('aria-expanded') !== 'true';

    if (state) {
      // If desktop nav, ensure all menus close before expanding new one.
      if (isDesktopNav()) {
        secondLevelNavMenus.forEach((el) => {
          el.querySelector(buttonSelector).setAttribute(
            'aria-expanded',
            'false',
          );
          el.querySelector('.primary-nav__menu--level-2').classList.remove(
            'is-active',
          );
        });
      }
      button.setAttribute('aria-expanded', 'true');
      topLevelMenuITem
        .querySelector('.primary-nav__menu--level-2')
        .classList.add('is-active');
    } else {
      button.setAttribute('aria-expanded', 'false');
      topLevelMenuITem.classList.remove('is-touch-event');
      topLevelMenuITem
        .querySelector('.primary-nav__menu--level-2')
        .classList.remove('is-active');
    }
  }

  Drupal.olivero.toggleSubNav = toggleSubNav;

  // Add event listeners onto each sub navigation parent and button.
  secondLevelNavMenus.forEach((el) => {
    const button = el.querySelector(
      '.primary-nav__button-toggle, .primary-nav__menu-link--button',
    );

    button.removeAttribute('aria-hidden');
    button.removeAttribute('tabindex');

    // If touch event, prevent mouseover event from triggering the submenu.
    el.addEventListener(
      'touchstart',
      () => {
        el.classList.add('is-touch-event');
      },
      { passive: true },
    );

    el.addEventListener('mouseover', () => {
      if (isDesktopNav() && !el.classList.contains('is-touch-event')) {
        el.classList.add('is-active-mouseover-event');
        toggleSubNav(el, true);

        // Timeout is added to ensure that users of assistive devices (such as
        // mouse grid tools) do not simultaneously trigger both the mouseover
        // and click events. When these events are triggered together, the
        // submenu to appear to not open.
        setTimeout(() => {
          el.classList.remove('is-active-mouseover-event');
        }, 500);
      }
    });

    button.addEventListener('click', () => {
      if (!el.classList.contains('is-active-mouseover-event')) {
        toggleSubNav(el);
      }
    });

    el.addEventListener('mouseout', () => {
      if (isDesktopNav()) {
        toggleSubNav(el, false);
      }
    });
  });

  /**
   * Close all second level sub navigation menus.
   */
  function closeAllSubNav() {
    secondLevelNavMenus.forEach((el) => {
      toggleSubNav(el, false);
    });
  }

  Drupal.olivero.closeAllSubNav = closeAllSubNav;

  /**
   * Checks if any sub navigation items are currently active.
   * @return {boolean} If sub nav is currently open.
   */
  function areAnySubNavsOpen() {
    let subNavsAreOpen = false;

    secondLevelNavMenus.forEach((el) => {
      const button = el.querySelector(
        '.primary-nav__button-toggle, .primary-nav__menu-link--button',
      );
      const state = button.getAttribute('aria-expanded') === 'true';

      if (state) {
        subNavsAreOpen = true;
      }
    });

    return subNavsAreOpen;
  }

  Drupal.olivero.areAnySubNavsOpen = areAnySubNavsOpen;

  // Ensure that desktop submenus close when ESC key is pressed.
  document.addEventListener('keyup', (e) => {
    if (e.key === 'Escape' || e.key === 'Esc') {
      if (isDesktopNav()) closeAllSubNav();
    }
  });

  // If user taps outside of menu, close all menus.
  document.addEventListener(
    'touchstart',
    (e) => {
      if (
        areAnySubNavsOpen() &&
        !e.target.matches(
          '.primary-nav__menu-item--has-children, .primary-nav__menu-item--has-children *',
        )
      ) {
        closeAllSubNav();
      }
    },
    { passive: true },
  );
})(Drupal);
