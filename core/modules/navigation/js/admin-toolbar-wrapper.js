/**
 *
 * Common JS managing behavior of admin-toolbar.
 *
 * Init Toolbar triggers.
 *
 * One trigger is button in Toolbar.
 * Another button in control panel on mobile.
 * Third is mobile shadow.
 * Fourth is close sidebar button on mobile.
 *
 * @type {Drupal~behavior}
 *
 * @prop {Drupal~behaviorAttach} attach
 */

(
  (Drupal, once) => {
    /**
     * Constant representing the event name for toggling the admin toolbar state.
     * @type {string}
     */
    const HTML_TRIGGER_EVENT = 'toggle-admin-toolbar';

    /**
     * Constant representing the event name for toggling the admin toolbar content.
     * @type {string}
     */
    const SIDEBAR_CONTENT_EVENT = 'toggle-admin-toolbar-content';

    Drupal.behaviors.navigationProcessHtmlListener = {
      /**
       * Attaches the behavior to the context element.
       *
       * @param {HTMLElement} context The context element to attach the behavior to.
       */
      attach: (context) => {
        if (context === document) {
          if (
            once(
              'admin-toolbar-document-triggers-listener',
              document.documentElement,
            ).length
          ) {
            const doc = document.documentElement;

            // This is special attribute which added to apply css
            // with animations and avoid layout shift.
            setTimeout(() => {
              doc.setAttribute('data-admin-toolbar-transitions', true);
            }, 200);

            doc.addEventListener(HTML_TRIGGER_EVENT, (e) => {
              // Prevents multiple triggering while transitioning.
              const newState = e.detail.state;
              const isUserInput = e.detail.manual;

              document.documentElement.setAttribute(
                'data-admin-toolbar',
                newState ? 'expanded' : 'collapsed',
              );

              // Set [data-admin-toolbar-body-scroll='locked']
              // See css/components/body-scroll-lock.pcss.css.

              document.documentElement.setAttribute(
                'data-admin-toolbar-body-scroll',
                newState ? 'locked' : 'unlocked',
              );

              doc.querySelector('.admin-toolbar').dispatchEvent(
                new CustomEvent(SIDEBAR_CONTENT_EVENT, {
                  detail: {
                    state: newState,
                  },
                }),
              );

              if (isUserInput) {
                document.documentElement.setAttribute(
                  'data-admin-toolbar-animating',
                  true,
                );
              }

              setTimeout(() => {
                document.documentElement.removeAttribute(
                  'data-admin-toolbar-animating',
                );
              }, 200);

              Drupal.displace(true);
            });

            /**
             * Initialize Drupal.displace()
             *
             * We add the displace attribute to a separate full width element because we
             * don't want this element to have transitions. Note that this element and the
             * navbar share the same exact width.
             */
            const initDisplace = () => {
              const displaceElement = doc
                .querySelector('.admin-toolbar')
                ?.querySelector('.admin-toolbar__displace-placeholder');
              const edge =
                document.documentElement.dir === 'rtl' ? 'right' : 'left';
              displaceElement?.setAttribute(`data-offset-${edge}`, '');
              Drupal.displace(true);
            };

            initDisplace();
          }
        }
      },
    };

    // Any triggers on page. Inside or outside sidebar.
    // For now button in sidebar + mobile header and background.

    Drupal.behaviors.navigationProcessToolbarTriggers = {
      attach: (context) => {
        const triggers = once(
          'admin-toolbar-trigger',
          '[aria-controls="admin-toolbar"]',
          context,
        );

        /**
         * Updates the state of all trigger elements based on the provided state.
         *
         * @param {boolean} toState The new state of the sidebar.
         */
        const toggleTriggers = (toState) => {
          triggers.forEach((trigger) => {
            trigger.setAttribute('aria-expanded', toState);
            const text = trigger.querySelector('[data-text]');
            if (text) {
              text.textContent = toState
                ? Drupal.t('Collapse sidebar')
                : Drupal.t('Expand sidebar');
            }
          });
        };

        let firstState =
          localStorage.getItem('Drupal.navigation.sidebarExpanded') !== 'false';

        // We need to display closed sidebar on init on mobile.
        if (window.matchMedia('(max-width: 1023px)').matches) {
          firstState = false;
        }

        // Set values on load.
        toggleTriggers(firstState);
        document.documentElement.dispatchEvent(
          new CustomEvent(HTML_TRIGGER_EVENT, {
            bubbles: true,
            detail: {
              state: firstState,
              manual: false,
            },
          }),
        );

        triggers.forEach((trigger) => {
          trigger.addEventListener('click', (e) => {
            const state =
              e.currentTarget.getAttribute('aria-expanded') === 'false';
            trigger.dispatchEvent(
              new CustomEvent(HTML_TRIGGER_EVENT, {
                bubbles: true,
                detail: {
                  state,
                  manual: true,
                },
              }),
            );
            toggleTriggers(state);
            localStorage.setItem('Drupal.navigation.sidebarExpanded', state);
          });
        });
      },
    };
  }
)(Drupal, once);
