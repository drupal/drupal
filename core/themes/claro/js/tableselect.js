/**
 * @file
 * Extends table select functionality for Claro.
 */

(($, Drupal, { tabbable }) => {
  Drupal.ClaroBulkActions = class {
    constructor(bulkActions) {
      this.bulkActions = bulkActions;
      this.form = this.bulkActions.closest('form');
      this.form.querySelectorAll('tr').forEach((element) => {
        element.classList.add('views-form__bulk-operations-row');
      });
      this.checkboxes = this.form.querySelectorAll(
        '[class$="bulk-form"]:not(.select-all) input[type="checkbox"]',
      );
      this.selectAll = this.form.querySelectorAll(
        '.select-all > [type="checkbox"]',
      );
      this.$tabbable = $(tabbable(this.form));
      this.bulkActionsSticky = false;
      this.scrollingTimeout = '';
      this.ignoreScrollEvent = false;

      $(this.checkboxes).on('change', (event) =>
        this.rowCheckboxHandler(event),
      );
      $(this.selectAll).on('change', (event) => this.selectAllHandler(event));
      this.$tabbable.on('focus', (event) => this.focusHandler(event));
      this.$tabbable.on('blur', (event) => this.blurHandler(event));

      // The will contain the CSS that hides the spacer during scroll
      // and resize.
      this.spacerCss = document.createElement('style');
      document.body.appendChild(this.spacerCss);

      const scrollResizeHandler = Drupal.debounce(() => {
        this.scrollResizeHandler();
      }, 10);
      $(window).on('scroll', () => scrollResizeHandler());
      $(window).on('resize', () => scrollResizeHandler());

      // Execute checkbox handler after the load event. This ensures that the
      // actions form is sticky if any checkboxes are already checked on page
      // load. One of the situations where it is possible to have pre-checked
      // checkboxes on load is when the page is requested via the back button.
      // window.addEventListener('load', () => this.rowCheckboxHandler({}));
      $(window).on('load', () => this.rowCheckboxHandler({}));
    }

    /**
     * Ensures that focusable elements hidden under a sticky remain focusable.
     *
     * @param {Object} event
     *   A jQuery Event object.
     */
    /* eslint-disable-next-line class-methods-use-this */
    blurHandler(event) {
      // This event handler should only proceed if the event came from direct
      // interaction with the form element. If this fires on events triggered
      // via JavaScript there may be undesirable side effects.
      if (!event.hasOwnProperty('isTrigger')) {
        const row = event.target.closest('tr');
        const nextSibling = row ? row.nextElementSibling : null;

        // Any row in this table potentially has a spacer div preceding it. The
        // spacer is added to prevent focusable elements from appearing
        // underneath the sticky Views Bulk Actions form. Any element underneath
        // this spacer is beneath the viewport. If an element beneath
        // the viewport receives focus and the previously focused element was
        // above the spacer, some browsers have difficulty determining how much
        // scrolling is necessary to bring the newly focused element into view.
        // To prevent this potential miscalculation, the spacer is momentarily
        // removed when blur occurs on rows preceding it. The spacer is
        // reintroduced immediately after the next item receives focus.
        if (
          nextSibling &&
          nextSibling.getAttribute('data-drupal-table-row-spacer')
        ) {
          nextSibling.parentNode.removeChild(nextSibling);
        }
      }
    }

    /**
     * If a partially covered element receives focus, scroll it into full view.
     *
     * @param {Object} event
     *   A jQuery Event object.
     */
    focusHandler(event) {
      // Do not scroll down when element inside bulk actions is focused.
      if (event.currentTarget.closest('[data-drupal-views-bulk-actions]')) {
        return;
      }
      const stickyRect = this.bulkActions.getBoundingClientRect();
      const stickyStart = stickyRect.y;
      const elementRect = event.target.getBoundingClientRect();
      const elementStart = elementRect.y;
      const elementEnd = elementStart + elementRect.height;
      if (elementEnd > stickyStart) {
        window.scrollBy(0, elementEnd - stickyStart);
      }
      this.underStickyHandler();
    }

    /**
     * Temporarily hides the spacer before calling underStickyHandler().
     *
     * The spacer is added to prevent the "show numbers" functionality of speech
     * navigation from labeling inputs under the stickied bulk actions form. It
     * does this by pushing these elements further down the page so they are out
     * of the viewport entirely. The presence of this spacer should be invisible
     * to users. Because this invisibility is partially achieved via
     * calculations based on scroll position and viewport size, the spacer is
     * hidden during these events, and reintroduced 500 milliseconds after all
     * scroll and resize events have completed.
     */
    scrollResizeHandler() {
      // Add CSS rule that hides the spacer. CSS is used instead of removing
      // the spacer from the DOM as the change occurs faster.
      this.spacerCss.innerHTML =
        '[data-drupal-table-row-spacer] { display: none; }';

      if (!this.ignoreScrollEvent) {
        // Remove the timeout that unhides the spacer. If this function is called,
        // then scrolling is still happening and spacers should stay hidden.
        clearTimeout(this.scrollingTimeout);

        // Shortly after scrolling tops, the spacer is re-added.
        this.scrollingTimeout = setTimeout(() => {
          this.spacerCss.innerHTML = '';
          this.underStickyHandler();
        }, 500);
      }
    }

    /**
     * Moves tabbable elements that are underneath the bulk actions form.
     *
     * Focusable elements inside a table row should not be positioned underneath
     * a sticky Views Bulk Action form. If this isn't prevented, it can be
     * confusing for speech navigation users when the "show numbers" feature
     * is enabled. Numbers will be provided for the elements within the Bulk
     * Actions form and the table row elements directly underneath, and it can
     * be difficult to discern which number corresponds to which element. To
     * prevent this confusion, a spacer div is added before the table row, and
     * this spacer pushes the row further down so the focusable elements are out
     * of viewport.
     */
    underStickyHandler() {
      document
        .querySelectorAll('[data-drupal-table-row-spacer]')
        .forEach((element) => {
          element.parentNode.removeChild(element);
        });

      if (this.bulkActionsSticky) {
        // Will be set to true as soon as the forEach() hits a row that is
        // completely under the sticky header, indicating that no further
        // processing is needed. Using a For...Of loop to accomplish this
        // is preferable, but not supported by IE11.
        let pastStickyHeader = false;
        const stickyRect = this.bulkActions.getBoundingClientRect();
        const stickyStart = stickyRect.y;
        const stickyEnd = stickyStart + stickyRect.height;

        // Loop through each table row. If a row has focusable elements under
        // the sticky Views Bulk Actions form, add a spacer that pushes the row
        // down the page and outside of the viewport.
        this.form.querySelectorAll('tbody tr').forEach((row) => {
          if (!pastStickyHeader) {
            const rowRect = row.getBoundingClientRect();
            const rowStart = rowRect.y;
            const rowEnd = rowStart + rowRect.height;
            if (rowStart > stickyEnd) {
              pastStickyHeader = true;
            } else if (rowEnd > stickyStart) {
              // Get padding amount for the row's cells, which are used to
              // determine where a row can be pushed out of the viewport
              // without any visible difference.
              const cellTopPadding = Array.from(
                row.querySelectorAll('td.views-field'),
              ).map((element) =>
                document.defaultView
                  .getComputedStyle(element, '')
                  .getPropertyValue('padding-top')
                  .replace('px', ''),
              );
              const minimumTopPadding = Math.min.apply(null, cellTopPadding);

              // If all parts of the table row that could be displaying content
              // are under the sticky.
              if (rowStart + minimumTopPadding >= stickyStart) {
                // If the row scrolled underneath the sticky has the element
                // with focus, the addition of a spacer can potentially create
                // an additional scroll event that can lead to unwanted results.
                // The variables below are used to identify this so a flag can
                // be set to bypass scroll handler actions in just those
                // instances.
                const oldScrollTop =
                  window.pageYOffset || document.documentElement.scrollTop;
                const scrollLeft =
                  window.pageXOffset || document.documentElement.scrollLeft;
                const rowContainsActiveElement = row.contains(
                  document.activeElement,
                );

                // If the row contains the active element, set the flag that
                // bypasses the actions of scrollResizeHandler() as a call to
                // window.scrollTo() may be needed.
                if (rowContainsActiveElement) {
                  this.ignoreScrollEvent = true;
                }

                // a spacer to push it out of the viewport. Because the elements
                // are fully underneath the sticky, the added spacer should not
                // result in any visible difference.
                const spacer = document.createElement('div');
                spacer.style.height = `${stickyRect.height}px`;
                spacer.setAttribute('data-drupal-table-row-spacer', true);
                row.parentNode.insertBefore(spacer, row);

                // Will be used to determine if a scroll position change
                // occurred due to adding the spacer.
                const newScrollTop =
                  window.pageYOffset || document.documentElement.scrollTop;

                // If the browser pushed the row back into the viewport after
                // the spacer was added, return the scroll position to the
                // intended location.
                const windowBottom =
                  window.innerHeight || document.documentElement.clientHeight;
                if (
                  rowContainsActiveElement &&
                  oldScrollTop !== newScrollTop &&
                  rowStart < windowBottom
                ) {
                  window.scrollTo(scrollLeft, oldScrollTop);
                }

                // Set this flag back to its default value of false.
                this.ignoreScrollEvent = false;
              }
            }
          }
        });
      }
    }

    /**
     * Triggered when the `select all` button is clicked.
     *
     * @param {Object} event
     *   A jQuery Event object.
     */
    selectAllHandler(event) {
      // This event handler should only proceed if the event came from direct
      // interaction with the form element. If this fires on events triggered
      // via JavaScript there may be undesirable side effects.
      if (!event.hasOwnProperty('isTrigger')) {
        const itemsCheckedCount = event.target.checked
          ? this.checkboxes.length
          : 0;
        this.updateStatus(itemsCheckedCount);
        this.underStickyHandler();
      }
    }

    /**
     * Triggered when a row is checked or unchecked.
     *
     * @param {Object} event
     *   A jQuery Event object.
     */
    rowCheckboxHandler(event) {
      // This event handler should only proceed if the event came from direct
      // interaction with the form element. If this fires on events triggered
      // via JavaScript there may be undesirable side effects.
      if (!event.hasOwnProperty('isTrigger')) {
        this.updateStatus(
          Array.prototype.slice
            .call(this.checkboxes)
            .filter((checkbox) => checkbox.checked).length,
        );
      }
    }

    /**
     * Update the bulk actions label and announcements.
     *
     * @param {number} count
     *   The number of checkboxes checked.
     */
    updateStatus(count) {
      // A status message that will be displayed in the bulk actions form and
      // announced by the screen reader.
      let statusMessage = '';

      // This will remain empty unless the actions form is made sticky and
      // previously was not.
      let operationsAvailableMessage = '';
      if (count > 0) {
        // Check if bulk operations has changed from not-sticky to sticky.
        if (!this.bulkActionsSticky) {
          operationsAvailableMessage = Drupal.t(
            'Bulk actions are now available. These actions will be applied to all selected items. This can be accessed via the "Skip to bulk actions" link that appears after every enabled checkbox. ',
          );
          this.bulkActionsSticky = true;

          // Run the underStickyHandler after the CSS animation completes.
          // Near the end of this there is an additional call to
          // underStickyHandler without a timeout. This covers users who have
          // animations disabled, and resets all items to visible if the bulk
          // actions form is no longer sticky.
          setTimeout(() => this.underStickyHandler(), 350);

          // When the actions form becomes sticky, it appears via an animation
          // at the bottom of the viewport. If this form is already above the
          // viewport, the animation would look odd. In these instances the
          // animation is bypassed.
          const stickyRect = this.bulkActions.getBoundingClientRect();

          const bypassAnimation =
            stickyRect.top + stickyRect.height <
            window.scrollY + window.innerHeight;

          // Determine add/remove with ternary since IE11 does not support the
          // second argument for classList.toggle().
          const classAction = bypassAnimation ? 'add' : 'remove';
          this.bulkActions.classList[classAction](
            'views-form__header--bypass-animation',
          );
        }

        statusMessage = Drupal.formatPlural(
          count,
          '1 item selected',
          '@count items selected',
        );
      } else {
        this.bulkActionsSticky = false;
        statusMessage = Drupal.t('No items selected');
        setTimeout(() => this.underStickyHandler(), 350);
      }

      // Update the attribute that instructs the bulk actions form to be sticky.
      this.bulkActions.setAttribute(
        'data-drupal-sticky-vbo',
        this.bulkActionsSticky,
      );

      // Update the bulk actions form label with the number of items checked.
      this.bulkActions.querySelector(
        '[data-drupal-views-bulk-actions-status]',
      ).textContent = statusMessage;

      // Announce these changes to the screen reader.
      Drupal.announce(operationsAvailableMessage + statusMessage);
      this.underStickyHandler();
    }
  };

  Drupal.behaviors.claroTableSelect = {
    attach(context) {
      const bulkActions = once(
        'ClaroBulkActions',
        '[data-drupal-views-bulk-actions]',
        context,
      );
      bulkActions.map(
        (bulkActionForm) =>
          /* eslint-disable-next-line no-new */
          new Drupal.ClaroBulkActions(bulkActionForm),
      );
    },
  };
})(jQuery, Drupal, window.tabbable, once);
