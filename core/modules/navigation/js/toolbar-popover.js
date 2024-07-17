/**
 *
 * Toolbar popover.
 *
 * @type {Drupal~behavior}
 *
 * @prop {Drupal~behaviorAttach} attach
 */

const POPOVER_OPEN_DELAY = 150;
const POPOVER_CLOSE_DELAY = 400;
const POPOVER_NO_CLICK_DELAY = 500;

((Drupal, once) => {
  Drupal.behaviors.navigationProcessPopovers = {
    /**
     * Attaches the behavior to the context element.
     *
     * @param {HTMLElement} context The context element to attach the behavior to.
     */
    attach: (context) => {
      once(
        'toolbar-popover',
        context.querySelectorAll('[data-toolbar-popover]'),
      ).forEach((popover) => {
        // This is trigger of popover. Currently only first level button.
        const button = popover.querySelector('[data-toolbar-popover-control]');
        // This is tooltip content. Currently child menus only.
        const tooltip = popover.querySelector('[data-toolbar-popover-wrapper]');

        if (!button || !tooltip) return;

        const handleMouseMove = (event) => {
          button.style.setProperty(
            '--safe-triangle-cursor-x',
            `${event.clientX}px`,
          );
          button.style.setProperty(
            '--safe-triangle-cursor-y',
            `${event.clientY}px`,
          );
        };

        const expandPopover = () => {
          popover.classList.add('toolbar-popover--expanded');
          button.dataset.drupalNoClick = 'true';
          tooltip.removeAttribute('inert');
          setTimeout(() => {
            delete button.dataset.drupalNoClick;
          }, POPOVER_NO_CLICK_DELAY);
        };

        const collapsePopover = () => {
          popover.classList.remove('toolbar-popover--expanded');
          tooltip.setAttribute('inert', true);
          delete button.dataset.drupalNoClick;
        };

        /**
         * We need to change state of trigger and popover.
         *
         * @param {boolean} state The popover state.
         *
         * @param {boolean} initialLoad Happens on page loads.
         */
        const toggleState = (state, initialLoad = false) => {
          /* eslint-disable-next-line no-unused-expressions */
          state && !initialLoad ? expandPopover() : collapsePopover();
          button.setAttribute('aria-expanded', state && !initialLoad);

          const text = button.querySelector('[data-toolbar-action]');
          if (text) {
            text.textContent = state
              ? Drupal.t('Collapse')
              : Drupal.t('Extend');
          }

          // Dispatch event to sidebar.js
          popover.dispatchEvent(
            new CustomEvent('toolbar-popover-toggled', {
              bubbles: true,
              detail: {
                state,
              },
            }),
          );
        };

        const isPopoverHoverOrFocus = () =>
          popover.contains(document.activeElement) || popover.matches(':hover');

        const delayedClose = () => {
          setTimeout(() => {
            if (isPopoverHoverOrFocus()) return;
            // eslint-disable-next-line no-use-before-define
            close();
          }, POPOVER_CLOSE_DELAY);
        };

        const open = () => {
          ['mouseleave', 'focusout'].forEach((e) => {
            button.addEventListener(e, delayedClose, false);
            tooltip.addEventListener(e, delayedClose, false);
          });
        };

        const close = () => {
          toggleState(false);
          ['mouseleave', 'focusout'].forEach((e) => {
            button.removeEventListener(e, delayedClose);
            tooltip.removeEventListener(e, delayedClose);
          });
        };

        button.addEventListener('mousemove', handleMouseMove);

        button.addEventListener('mouseover', () => {
          // This is not needed because no hover on mobile.
          // @todo test is after.

          if (window.matchMedia('(max-width: 1023px)').matches) {
            return;
          }

          setTimeout(() => {
            // If it is accident hover ignore it.
            // If in this timeout popover already opened by click.
            if (
              !button.matches(':hover') ||
              !button.getAttribute('aria-expanded') === 'false'
            ) {
              return;
            }

            toggleState(true);
            open();
          }, POPOVER_OPEN_DELAY);
        });

        button.addEventListener('click', (e) => {
          const state =
            e.currentTarget.getAttribute('aria-expanded') === 'false';

          if (!e.currentTarget.dataset.drupalNoClick) {
            toggleState(state);
          }
        });

        // Listens events from sidebar.js.
        popover.addEventListener('toolbar-popover-close', () => {
          close();
        });

        // TODO: Add toggle with state.
        popover.addEventListener('toolbar-popover-open', () => {
          toggleState(true);
        });

        // Listens events from toolbar-menu.js
        popover.addEventListener('toolbar-active-url', () => {
          toggleState(true, true);
        });
      });
    },
  };
})(Drupal, once);
