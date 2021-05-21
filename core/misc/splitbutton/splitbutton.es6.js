/**
 * @file
 * Splitbutton feature.
 */

((Drupal, Popper) => {
  /**
   * Constructs a splitbutton UI element.
   *
   * @param {HTMLElement} splitbutton
   *   Markup that includes actionable list items such as links or submit
   *   inputs, and a button that toggles their visibility.
   *
   * @return {Drupal.SplitButton}
   *   Class representing a splitbutton UI element.
   */
  Drupal.SplitButton = class {
    constructor(splitbutton) {
      const dataPrefix = 'data-drupal-splitbutton-';
      this.dataPrefix = dataPrefix;
      splitbutton.setAttribute(`${dataPrefix}enabled`, '');

      this.keyCode = Object.freeze({
        TAB: 9,
        RETURN: 13,
        ESC: 27,
        SPACE: 32,
        UP: 38,
        DOWN: 40,
      });
      this.listItems = [];

      this.splitbutton = splitbutton;
      this.trigger = splitbutton.querySelector(`[${dataPrefix}trigger]`);
      this.list = splitbutton.querySelector(`[${dataPrefix}target]`);
      this.triggerBox = splitbutton.querySelector(`[${dataPrefix}main]`);

      this.triggerBox.addEventListener('mouseenter', () => this.activeIn());
      this.triggerBox.addEventListener('mouseleave', () => this.hoverOut());
      this.list.addEventListener('mouseenter', () => this.activeIn());
      this.list.addEventListener('mouseleave', () => this.hoverOut());
      this.splitbutton.addEventListener('focusin', () => this.activeIn());
      this.splitbutton.addEventListener('focusout', () => this.focusOut());
      this.splitbutton.addEventListener('keydown', (e) => this.keydown(e));
      this.trigger.addEventListener('click', (e) => this.clickToggle(e));
      if (this.splitbutton.hasAttribute(`${dataPrefix}hover`)) {
        this.splitbutton.addEventListener('mouseenter', () => this.open());
      }
    }

    /**
     * Populate instance variables that facilitate keyboard navigation.
     */
    initListItems() {
      // If this.listItems is empty, the initialization hasn't occurred yet.
      if (this.listItems.length === 0) {
        const itemTags =
          this.list.getAttribute(`${this.dataPrefix}tags`) ||
          'a, input, button';
        Array.prototype.slice
          .call(this.list.querySelectorAll(itemTags))
          .forEach((item, index) => {
            // Add attribute to each item to identify its focus order.
            item.setAttribute(`${this.dataPrefix}item`, index);
            item.classList.add('splitbutton__operation-list-item');
            this.listItems.push(item);
            this.lastItemIndex = index;
          });
      }
    }

    /**
     * Initialize positioning of items with PopperJS.
     */
    initPopper() {
      this.popper = Popper.createPopper(this.triggerBox, this.list, {
        placement: 'bottom-start',
        modifiers: [
          {
            name: 'flip',
            options: {
              fallbackPlacements: [],
            },
          },
        ],
      });
    }

    /**
     * Toggle button click listener.
     *
     * @param {Event} e
     *   The click event.
     */
    clickToggle(e) {
      e.preventDefault();
      e.stopPropagation();
      const state = this.splitbutton.hasAttribute(`${this.dataPrefix}open`)
        ? 'close'
        : 'open';
      this[state](e.detail === 0);
    }

    /**
     * Toggles visibility of menu items
     *
     * @param {boolean} show
     *   Force visibility based on this value.
     */
    toggle(show) {
      const isBool = typeof show === 'boolean';
      show = isBool
        ? show
        : !this.splitbutton.hasAttribute(`${this.dataPrefix}open`);
      const expanded = show ? 'true' : 'false';
      if (show) {
        this.splitbutton.setAttribute(`${this.dataPrefix}open`, '');
      } else {
        this.splitbutton.removeAttribute(`${this.dataPrefix}open`);
      }
      this.trigger.setAttribute('aria-expanded', expanded);
    }

    /**
     * Opens splitbutton menu.
     *
     * @param {bool} focusFirst
     *   If true the first item in the list will be focused.
     */
    open(focusFirst = true) {
      // The items width should be at least as wide as the main splitbutton
      // element.
      this.list.style['min-width'] = `${this.triggerBox.offsetWidth}px`;
      this.initListItems();
      this.toggle(true);
      if (!this.hasOwnProperty('popper')) {
        this.initPopper();
      } else {
        this.popper.forceUpdate();
      }

      if (focusFirst) {
        // Wrap in a zero-wait timeout to ensure it isn't called until
        // initListItems() completes.
        setTimeout(() => this.focusFirst(), 0);
      }
    }

    /**
     * Closes splitbutton list.
     */
    close() {
      this.toggle(false);
    }

    /**
     * Event listener for hover and focus in.
     */
    activeIn() {
      // Clear any previous timer we were using.
      if (this.timerID) {
        window.clearTimeout(this.timerID);
      }
    }

    /**
     * Event listener for hover and focus out.
     */
    hoverOut() {
      // Wait half a second before closing, to prevent unwanted closings due to
      // the pointer briefly straying from the target while moving to a new item
      // within the splitbutton.
      this.timerID = window.setTimeout(() => this.toggle(false), 500);
    }

    focusOut() {
      // Provide a brief timeout before closing to prevent flickering.
      this.timerID = window.setTimeout(() => this.toggle(false), 50);
    }

    /**
     * Keydown listener.
     *
     * @param {Event} e
     *   The keydown event.
     */
    keydown(e) {
      let preventDefault = true;

      if (
        e.ctrlKey ||
        e.altKey ||
        e.metaKey ||
        e.keyCode === this.keyCode.SPACE ||
        e.keyCode === this.keyCode.RETURN ||
        (e.keyCode === this.keyCode.TAB &&
          e.target.getAttribute(`${this.dataPrefix}item`) === null)
      ) {
        return;
      }

      switch (e.keyCode) {
        case this.keyCode.ESC:
          this.focusTrigger();
          this.close();
          break;

        case this.keyCode.UP:
          if (this.splitbutton.hasAttribute(`${this.dataPrefix}open`)) {
            this.focusPrev(e);
          } else {
            this.open(false);
            this.focusLast();
          }
          break;

        case this.keyCode.DOWN:
          if (this.splitbutton.hasAttribute(`${this.dataPrefix}open`)) {
            this.focusNext(e);
          } else {
            this.open();
            this.focusFirst();
          }
          break;

        // case this.keyCode.TAB:
        //   this.focusTrigger();
        //   this.close(true);
        //   break;

        default:
          preventDefault = false;
          break;
      }

      if (preventDefault) {
        e.stopPropagation();
        e.preventDefault();
      }
    }

    /**
     * Assigns focus to the next list item.
     *
     * @param {Event} e
     *   A keydown event.
     */
    focusNext(e) {
      const currentItem = e.target.getAttribute(`${this.dataPrefix}item`);
      if (currentItem === null) {
        this.listItems[0].focus();
      } else {
        const nextIndex = parseInt(currentItem, 10) + 1;
        const focusIndex = nextIndex > this.lastItemIndex ? 0 : nextIndex;
        this.listItems[focusIndex].focus();
      }
    }

    /**
     * Assigns focus to the previous list item.
     *
     * @param {Event} e
     *   A keydown event.
     */
    focusPrev(e) {
      const currentItem = e.target.getAttribute(`${this.dataPrefix}item`);
      if (currentItem === null) {
        this.listItems[this.lastItemIndex].focus();
      } else {
        const prevIndex = parseInt(currentItem, 10) - 1;
        const focusIndex = prevIndex < 0 ? this.lastItemIndex : prevIndex;
        this.listItems[focusIndex].focus();
      }
    }

    /**
     * Assigns focus to the trigger element.
     */
    focusTrigger() {
      this.trigger.focus();
    }

    /**
     * Assigns focus to the first list item.
     */
    focusFirst() {
      this.listItems[0].focus();
    }

    /**
     * Assigns focus to the last menu item.
     */
    focusLast() {
      this.listItems[this.lastItemIndex].focus();
    }
  };
})(Drupal, Popper);
