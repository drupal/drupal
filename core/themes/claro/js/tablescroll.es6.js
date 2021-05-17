/**
 * @file
 * Horizontally scrollable table functionality.
 */
((Drupal, $, debounce) => {
  Drupal.TableScroll = class {
    constructor(table) {
      this.table = table;
      this.wrapper = table.parentElement;
      this.wrapperOutline = this.wrapper.parentElement;
      this.stickyHeader = null;
      this.fixedTopValue = null;

      const horizontalScrollSelectors = debounce(
        this.applyHorizontalScrollSelectors.bind(this),
        150,
      );
      const $window = $(window);
      const $document = $(document);
      const $wrapper = $(this.wrapper);

      // Initialize horizontal scroll selectors.
      horizontalScrollSelectors();

      // When the window is resized, the toolbar tray is toggled, or a table is
      // horizontally scrolled, trigger a function that updates selectors that
      // indicate the directions a table can be scrolled.
      $wrapper.on('scroll.TableScroll', horizontalScrollSelectors);
      $window.on('resize.TableScroll', horizontalScrollSelectors);
      $document.on('drupalToolbarTrayChange', horizontalScrollSelectors);

      // When a sticky header is added, prepare it for use inside a scrollable
      // table.
      $document.on('tableheaderCreateSticky', () => {
        this.stickyHeader = this.wrapper.querySelector('.sticky-header');

        // Apply clipping to the sticky header so it does not overflow it
        // container.
        this.clipFixedStickyHeader();

        // When a table is horizontally scrolled, trigger a function that allows
        // the sticky header to horizontally scroll within its container.
        $wrapper.on(
          'scroll.TableScroll',
          debounce(this.horizontalScrollHandleStickyHeader.bind(this), 150),
        );

        const updateStickyHeader = debounce(
          this.updateStickyHeader.bind(this),
          150,
        );
        // When the window scrolls or is resized, trigger a function that
        // reverts any changes made to accommodate horizontal scrolling.
        $window.on('scroll.TableScroll resize.TableScroll', updateStickyHeader);

        // When the toolbar tray is toggled, the dimensions of the table could
        // change, so trigger a function that applies any needed changes to the
        // sticky header due to the dimensions changing.
        $document.on('drupalToolbarTrayChange', updateStickyHeader);
      });
    }

    /**
     * Updates sticky header in response to events
     */
    updateStickyHeader() {
      // Absolute positioning of the sticky header is only needed when a table
      // is being horizontally scrolled. This handler is only called when that
      // is definitively not happening, so switch the sticky header back to
      // fixed positioning if needed.
      if (this.stickyHeader.style.position === 'absolute') {
        this.makeStickyHeaderFixed();
      } else {
        // Apply clipping to the fixed sticky header so it does not overflow its
        // container.
        this.clipFixedStickyHeader();
      }
    }

    /**
     * Converts an absolutely positioned sticky header to fixed positioning.
     */
    makeStickyHeaderFixed() {
      this.stickyHeader.style.position = 'fixed';
      // Left should equal the sticky header's position inside the window.
      this.stickyHeader.style.left = `${
        this.stickyHeader.getBoundingClientRect().left
      }px`;
      // Use the value stored in horizontalScrollHandleStickyHeader().
      this.stickyHeader.style.top = `${this.fixedTopValue}px`;

      // Now that the sticky header is again fixed, apply any clip styling
      // needed so it does not overflow the table container.
      this.clipFixedStickyHeader();
    }

    /**
     * Called when a table is scrolled horizontally.
     */
    horizontalScrollHandleStickyHeader() {
      // When a table is scrolled horizontally, sticky headers with fixed
      // positioning are be converted to absolute so they respond to the
      // scroll events.
      if (this.stickyHeader.style.position === 'fixed') {
        const wrapperTopOffset = this.wrapper.getBoundingClientRect().top;
        const stickyTopOffset = this.stickyHeader.getBoundingClientRect().top;

        // Store the current top offset value so it can be reapplied when
        // positioning is switched back to fixed.
        this.fixedTopValue = stickyTopOffset;

        this.stickyHeader.style.position = 'absolute';

        // The sticky header left position can be flush with the container.
        this.stickyHeader.style.left = 0;

        // The value of top is calculated so the sticky header remains in the
        // same position despite having its position style changed.
        this.stickyHeader.style.top = `${
          Math.abs(wrapperTopOffset) + stickyTopOffset
        }px`;

        // No clipping is needed when absolutely positioned as overflow is
        // contained.
        this.stickyHeader.style.clip = 'unset';
      }
    }

    /**
     * Adds/removes selectors based on the container's scrollable overflow.
     */
    applyHorizontalScrollSelectors() {
      const { wrapperOutline } = this;
      const wrapperBoundingRect = this.wrapper.getBoundingClientRect();
      const tableBoundingRect = this.table.getBoundingClientRect();
      const wrapperWidth = wrapperBoundingRect.width;
      const wrapperOffset = wrapperBoundingRect.left;
      const tableWidth = tableBoundingRect.width;
      const tableOffset = tableBoundingRect.left;
      const $wrapperOutline = $(wrapperOutline);

      // Add/remove classes used for styles that indicate available scrolling.
      $wrapperOutline.toggleClass(
        'scrollable-table-outline--scroll',
        wrapperWidth < tableWidth,
      );
      $wrapperOutline.toggleClass(
        'scrollable-table-outline--scroll-left',
        wrapperOffset > tableOffset,
      );
      $wrapperOutline.toggleClass(
        'scrollable-table-outline--scroll-right',
        Math.ceil(wrapperOffset + wrapperWidth) <
          Math.floor(tableOffset + tableWidth),
      );
    }

    /**
     * Clips a fixed sticky header so it does not overflow its container.
     */
    clipFixedStickyHeader() {
      const wrapperBoundingRect = this.wrapper.getBoundingClientRect();
      const wrapperWidth = wrapperBoundingRect.width;
      const { scrollLeft } = this.wrapper;

      // If the viewport is wide enough to display the entire header, no
      // clipping is needed, and left offset is only determined by container
      // offset.
      if (wrapperWidth < this.stickyHeader.clientWidth) {
        this.stickyHeader.style.clip = `rect(auto,  ${
          wrapperWidth + scrollLeft
        }px, auto, ${scrollLeft - 1}px)`;
      } else {
        this.stickyHeader.style.clip = 'unset';
      }
    }
  };
})(Drupal, jQuery, Drupal.debounce);
