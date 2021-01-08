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
      topLevelMenuITem
        .querySelector('.primary-nav__menu--level-2')
        .classList.remove('is-active');
    }
  }

  Drupal.olivero.toggleSubNav = toggleSubNav;

  // Add hover and click event listeners onto each sub navigation parent and its
  // button.
  secondLevelNavMenus.forEach((el) => {
    const button = el.querySelector(
      '.primary-nav__button-toggle, .primary-nav__menu-link--button',
    );

    button.removeAttribute('aria-hidden');
    button.removeAttribute('tabindex');

    button.addEventListener('click', (e) => {
      const topLevelMenuITem = e.currentTarget.parentNode;
      toggleSubNav(topLevelMenuITem);
    });

    el.addEventListener('mouseover', (e) => {
      if (isDesktopNav()) {
        toggleSubNav(e.currentTarget, true);
      }
    });

    el.addEventListener('mouseout', (e) => {
      if (isDesktopNav()) {
        toggleSubNav(e.currentTarget, false);
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
      const button = el.querySelector('.primary-nav__button-toggle');
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
    if (e.keyCode === 27 && isDesktopNav()) {
      closeAllSubNav();
    }
  });
})(Drupal);
