/**
 * @file
 *
 * Sidebar component.
 *
 * Only few common things. Like close all popovers when one is opened.
 *
 * @type {Drupal~behavior}
 *
 * @prop {Drupal~behaviorAttach} attach
 *   Attaches the behavior to the `.admin-toolbar` element.
 */

(
  (Drupal, once) => {
    /**
     * Drupal behaviors object.
     *
     * @type {Drupal~behaviors}
     */

    Drupal.behaviors.navigation = {
      attach(context) {
        /**
         * Sidebar element with the `.admin-toolbar` class.
         *
         * @type {HTMLElement}
         */
        once('navigation', '.admin-toolbar', context).forEach((sidebar) => {
          const backButton = sidebar.querySelector(
            '[data-toolbar-back-control]',
          );
          if (!backButton) {
            // We're in layout editing mode and the .admin-toolbar we have in
            // scope here is the empty one that only exists to leave space for
            // the one added by layout builder. We need to use an empty
            // .admin-toolbar element because the css uses the adjacent
            // sibling selector.
            // @see \navigation_page_top();
            return;
          }

          /**
           * All menu triggers.
           *
           * @type {NodeList}
           */
          const buttons = sidebar.querySelectorAll(
            '[data-toolbar-menu-trigger]',
          );

          /**
           * All popovers and tooltip triggers.
           *
           * @type {NodeList}
           */
          // const popovers = sidebar.querySelectorAll('[data-toolbar-popover]');

          /**
           * NodeList of all tooltip elements.
           *
           * @type {NodeList}
           */
          const tooltips = sidebar.querySelectorAll('[data-drupal-tooltip]');

          const closeButtons = () => {
            buttons.forEach((button) => {
              button.dispatchEvent(
                new CustomEvent('toolbar-menu-set-toggle', {
                  detail: {
                    state: false,
                  },
                }),
              );
            });
          };

          const closePopovers = (current = false) => {
            // TODO: Find way to use popovers variable.
            // This change needed because BigPipe replaces user popover.
            sidebar
              .querySelectorAll('[data-toolbar-popover]')
              .forEach((popover) => {
                if (
                  current &&
                  current instanceof Element &&
                  popover.isEqualNode(current)
                ) {
                  return;
                }
                popover.dispatchEvent(
                  new CustomEvent('toolbar-popover-close', {}),
                );
              });
          };

          // Add click event listeners to all buttons and then contains the callback
          // to expand / collapse the button's menus.
          sidebar.addEventListener('click', (e) => {
            if (e.target.matches('button, button *')) {
              e.target.closest('button').focus();
            }
          });

          // We want to close all popovers when we close sidebar.
          sidebar.addEventListener('toggle-admin-toolbar-content', (e) => {
            if (!e.detail.state) {
              closePopovers();
            }
          });

          // When any popover opened we close all others.
          sidebar.addEventListener('toolbar-popover-toggled', (e) => {
            if (e.detail.state) {
              closeButtons();
              closePopovers(e.target);
            }
          });

          // When any menu opened we close all others.
          sidebar.addEventListener('toolbar-menu-toggled', (e) => {
            if (e.detail.state) {
              // We want to close buttons on when new opened only if they are on same level.
              const targetLevel = e.detail.level;
              buttons.forEach((button) => {
                const buttonLevel = button.dataset.toolbarMenuTrigger;
                if (
                  !button.isEqualNode(e.target) &&
                  +buttonLevel === +targetLevel
                ) {
                  button.dispatchEvent(
                    new CustomEvent('toolbar-menu-set-toggle', {
                      detail: {
                        state: false,
                      },
                    }),
                  );
                }
              });
            }
          });
          backButton.addEventListener('click', closePopovers);

          // Tooltips triggered on hover and focus so add an extra event listener
          // to close all popovers.
          tooltips.forEach((tooltip) => {
            ['mouseover', 'focus'].forEach((e) => {
              tooltip.addEventListener(e, closePopovers);
            });
          });
        });
      },
    };
  }
)(Drupal, once);
