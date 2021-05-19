((Drupal) => {
  const { isDesktopNav } = Drupal.olivero;
  const secondLevelNavMenus = document.querySelectorAll(
    '.primary-nav__menu-item--has-children',
  );

  /**
   * Shows and hides the specified menu item's second level submenu.
   *
   * @param {element} topLevelMenuItem - the <li> element that is the container for the menu and submenus.
   * @param {boolean} [toState] - Optional state where we want the submenu to end up.
   */
  function toggleSubNav(topLevelMenuItem, toState) {
    const buttonSelector =
      '.primary-nav__button-toggle, .primary-nav__menu-link--button';
    const button = topLevelMenuItem.querySelector(buttonSelector);
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
            'is-active-menu-parent',
          );
          el.querySelector('.primary-nav__menu-🥕').classList.remove(
            'is-active-menu-parent',
          );
        });
      }
      button.setAttribute('aria-expanded', 'true');
      topLevelMenuItem
        .querySelector('.primary-nav__menu--level-2')
        .classList.add('is-active-menu-parent');
      topLevelMenuItem
        .querySelector('.primary-nav__menu-🥕')
        .classList.add('is-active-menu-parent');
    } else {
      button.setAttribute('aria-expanded', 'false');
      topLevelMenuItem.classList.remove('is-touch-event');
      topLevelMenuItem
        .querySelector('.primary-nav__menu--level-2')
        .classList.remove('is-active-menu-parent');
      topLevelMenuItem
        .querySelector('.primary-nav__menu-🥕')
        .classList.remove('is-active-menu-parent');
    }
  }

  Drupal.olivero.toggleSubNav = toggleSubNav;

  /**
   * Sets a timeout and closes current desktop navigation submenu if it
   * does not contain the focused element.
   *
   * @param {object} e - event object
   */
  function handleBlur(e) {
    if (!Drupal.olivero.isDesktopNav()) return;

    setTimeout(() => {
      const menuParentItem = e.target.closest(
        '.primary-nav__menu-item--has-children',
      );
      if (!menuParentItem.contains(document.activeElement)) {
        toggleSubNav(menuParentItem, false);
      }
    }, 200);
  }

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
      if (
        isDesktopNav() &&
        !document.activeElement.matches(
          '[aria-expanded="true"], .is-active-menu-parent *',
        )
      ) {
        toggleSubNav(el, false);
      }
    });

    el.addEventListener('blur', handleBlur, true);
  });

  /**
   * Close all second level sub navigation menus.
   */
  function closeAllSubNav() {
    secondLevelNavMenus.forEach((el) => {
      // Return focus to the toggle button if the submenu contains focus.
      if (el.contains(document.activeElement)) {
        el.querySelector(
          '.primary-nav__button-toggle, .primary-nav__menu-link--button',
        ).focus();
      }
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
        !e.target.matches('.header-nav, .header-nav *')
      ) {
        closeAllSubNav();
      }
    },
    { passive: true },
  );
})(Drupal);
