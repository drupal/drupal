/**
 * @file
 * Keyboard navigation component.
 */

((Drupal, once, { focusable }) => {
  /**
   * Attaches the keyboard navigation functionality.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior to the `.admin-toolbar` element.
   */
  Drupal.behaviors.keyboardNavigation = {
    attach: (context) => {
      once('keyboard-processed', '.admin-toolbar', context).forEach(
        (sidebar) => {
          const IS_RTL = document.documentElement.dir === 'rtl';

          const isInteractive = (element) =>
            element.getAttribute('aria-expanded');

          const getFocusableGroup = (element) =>
            element.closest('[class*="toolbar-menu--level-"]') ||
            element.closest('[data-toolbar-popover-wrapper]') ||
            element.closest('.admin-toolbar');

          const findFirstElementByChar = (focusableElements, targetChar) => {
            const elementWIthChar = Array.prototype.find.call(
              focusableElements,
              (element) => {
                const dataText = element.dataset.indexText;
                return dataText && dataText[0] === targetChar;
              },
            );

            return elementWIthChar;
          };

          const checkChar = ({ key, target }) => {
            const currentGroup = getFocusableGroup(target);
            const foundElementWithIndexChar = findFirstElementByChar(
              focusable(currentGroup),
              key,
            );
            if (foundElementWithIndexChar) {
              foundElementWithIndexChar.focus();
            }
          };

          const focusFirstInGroup = (focusableElements) => {
            focusableElements[0].focus();
          };

          const focusLastInGroup = (focusableElements) => {
            focusableElements[focusableElements.length - 1].focus();
          };

          const focusNextInGroup = (focusableElements, element) => {
            const currentIndex = Array.prototype.indexOf.call(
              focusableElements,
              element,
            );

            if (currentIndex === focusableElements.length - 1) {
              focusableElements[0].focus();
            } else {
              focusableElements[currentIndex + 1].focus();
            }
          };

          const focusPreviousInGroup = (focusableElements, element) => {
            const currentIndex = Array.prototype.indexOf.call(
              focusableElements,
              element,
            );

            if (currentIndex === 0) {
              focusableElements[focusableElements.length - 1].focus();
            } else {
              focusableElements[currentIndex - 1].focus();
            }
          };

          const toggleMenu = (element, state) =>
            element.dispatchEvent(
              new CustomEvent('toolbar-menu-set-toggle', {
                bubbles: false,
                detail: {
                  state,
                },
              }),
            );

          const closePopover = (element) =>
            element.dispatchEvent(
              new CustomEvent('toolbar-popover-close', { bubbles: true }),
            );

          const openPopover = (element) =>
            element.dispatchEvent(
              new CustomEvent('toolbar-popover-open', { bubbles: true }),
            );

          const focusClosestPopoverTrigger = (element) => {
            element
              .closest('[data-toolbar-popover]')
              ?.querySelector('[data-toolbar-popover-control]')
              ?.focus();
          };

          const focusFirstMenuElement = (element) => {
            const elements = focusable(
              element
                .closest('.toolbar-menu__item')
                ?.querySelector('.toolbar-menu'),
            );
            if (elements?.length) {
              elements[0].focus();
            }
          };

          const focusFirstPopoverElement = (element) => {
            // Zero is always popover trigger.
            // And Popover header can be not interactive.
            const elements = focusable(
              element.closest('[data-toolbar-popover]'),
            );
            if (elements?.length >= 1) {
              elements[1].focus();
            }
          };

          const focusLastPopoverElement = (element) => {
            const elements = focusable(
              element.closest('[data-toolbar-popover]'),
            );
            if (elements?.length > 0) {
              elements[elements.length - 1].focus();
            }
          };

          const closeNonInteractiveElement = (element) => {
            // If we are inside submenus.
            if (element.closest('[class*="toolbar-menu--level-"]')) {
              const trigger =
                element.closest('.toolbar-menu')?.previousElementSibling;
              toggleMenu(trigger, false);
              trigger.focus();
            } else {
              closePopover(element);
              focusClosestPopoverTrigger(element);
            }
          };

          const openInteractiveElement = (element) => {
            // If menu button.
            if (element.hasAttribute('data-toolbar-menu-trigger')) {
              toggleMenu(element, true);
              focusFirstMenuElement(element);
            }
            // If popover trigger.
            if (element.hasAttribute('data-toolbar-popover-control')) {
              openPopover(element);
              focusFirstPopoverElement(element);
            }
          };

          const closeInteractiveElement = (element) => {
            // If menu button.
            if (element.hasAttribute('data-toolbar-menu-trigger')) {
              if (element.getAttribute('aria-expanded') === 'false') {
                closeNonInteractiveElement(element);
              } else {
                toggleMenu(element, false);
                focusFirstMenuElement(element);
              }
            }
            // If popover trigger.
            if (element.hasAttribute('data-toolbar-popover-control')) {
              openPopover(element);
              focusLastPopoverElement(element);
            }
          };

          const arrowsSideControl = ({ key, target }) => {
            if (
              (key === 'ArrowRight' && !IS_RTL) ||
              (key === 'ArrowLeft' && IS_RTL)
            ) {
              if (isInteractive(target)) {
                openInteractiveElement(target);
                // If also we want to care about expand button.
                if (
                  target.getAttribute('aria-controls') === 'admin-toolbar' &&
                  target.getAttribute('aria-expanded') === 'false'
                ) {
                  target.click();
                }
              }
            } else if (
              (key === 'ArrowRight' && IS_RTL) ||
              (key === 'ArrowLeft' && !IS_RTL)
            ) {
              if (isInteractive(target)) {
                closeInteractiveElement(target);

                // If also we want to care about expand button.
                if (
                  target.getAttribute('aria-controls') === 'admin-toolbar' &&
                  target.getAttribute('aria-expanded') !== 'false'
                ) {
                  target.click();
                }
              } else {
                closeNonInteractiveElement(target);
              }
            }
          };

          const arrowsDirectionControl = ({ key, target }) => {
            const focusableElements = focusable(getFocusableGroup(target));
            if (key === 'ArrowUp') {
              focusPreviousInGroup(focusableElements, target);
            } else if (key === 'ArrowDown') {
              focusNextInGroup(focusableElements, target);
            }
          };

          sidebar.addEventListener('keydown', (e) => {
            switch (e.key) {
              case 'Escape':
                closePopover(e.target);
                focusClosestPopoverTrigger(e.target);
                break;

              case 'ArrowLeft':
              case 'ArrowRight':
                e.preventDefault();
                arrowsSideControl(e);
                break;
              case 'ArrowDown':
              case 'ArrowUp':
                e.preventDefault();
                arrowsDirectionControl(e);
                break;
              case 'Home':
                e.preventDefault();
                focusFirstInGroup(getFocusableGroup(e.target));
                break;
              case 'End':
                e.preventDefault();
                focusLastInGroup(getFocusableGroup(e.target));
                break;
              default:
                checkChar(e);
                break;
            }
          });
        },
      );
    },
  };
})(Drupal, once, window.tabbable);
