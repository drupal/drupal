(($, Drupal) => {
  /**
   * Models the state of a contextual link's trigger, list & region.
   */
  Drupal.contextual.ContextualModelView = class {
    constructor($contextual, $region, options) {
      this.title = options.title || '';
      this.regionIsHovered = false;
      this._hasFocus = false;
      this._isOpen = false;
      this._isLocked = false;
      this.strings = options.strings;
      this.timer = NaN;
      this.modelId = btoa(Math.random()).substring(0, 12);
      this.$region = $region;
      this.$contextual = $contextual;

      if (!document.body.classList.contains('touchevents')) {
        this.$region.on({
          mouseenter: () => {
            this.regionIsHovered = true;
          },
          mouseleave: () => {
            this.close().blur();
            this.regionIsHovered = false;
          },
          'mouseleave mouseenter': () => this.render(),
        });
        this.$contextual.on('mouseenter', () => {
          this.focus();
          this.render();
        });
      }

      this.$contextual.on(
        {
          click: () => {
            this.toggleOpen();
          },
          touchend: () => {
            Drupal.contextual.ContextualModelView.touchEndToClick();
          },
          focus: () => {
            this.focus();
          },
          blur: () => {
            this.blur();
          },
          'click blur touchend focus': () => this.render(),
        },
        '.trigger',
      );

      this.$contextual.on(
        {
          click: () => {
            this.close().blur();
          },
          touchend: (event) => {
            Drupal.contextual.ContextualModelView.touchEndToClick(event);
          },
          focus: () => {
            this.focus();
          },
          blur: () => {
            this.waitCloseThenBlur();
          },
          'click blur touchend focus': () => this.render(),
        },
        '.contextual-links a',
      );

      this.render();

      // Let other JavaScript react to the adding of a new contextual link.
      $(document).trigger('drupalContextualLinkAdded', {
        $el: $contextual,
        $region,
        model: this,
      });
    }

    /**
     * Updates the rendered representation of the current contextual links.
     */
    render() {
      const { isOpen } = this;
      const isVisible = this.isLocked || this.regionIsHovered || isOpen;
      this.$region.toggleClass('focus', this.hasFocus);
      this.$contextual
        .toggleClass('open', isOpen)
        // Update the visibility of the trigger.
        .find('.trigger')
        .toggleClass('visually-hidden', !isVisible);

      this.$contextual.find('.contextual-links').prop('hidden', !isOpen);
      const trigger = this.$contextual.find('.trigger').get(0);
      trigger.textContent = Drupal.t('@action @title configuration options', {
        '@action': !isOpen ? this.strings.open : this.strings.close,
        '@title': this.title,
      });
      trigger.setAttribute('aria-pressed', isOpen);
    }

    /**
     * Prevents delay and simulated mouse events.
     *
     * @param {jQuery.Event} event the touch end event.
     */
    static touchEndToClick(event) {
      event.preventDefault();
      event.target.click();
    }

    /**
     * Set up a timeout to allow a user to tab between the trigger and the
     * contextual links without the menu dismissing.
     */
    waitCloseThenBlur() {
      this.timer = window.setTimeout(() => {
        this.isOpen = false;
        this.hasFocus = false;
        this.render();
      }, 150);
    }

    /**
     * Opens or closes the contextual link.
     *
     * If it is opened, then also give focus.
     *
     * @return {Drupal.contextual.ContextualModelView}
     *   The current contextual model view.
     */
    toggleOpen() {
      const newIsOpen = !this.isOpen;
      this.isOpen = newIsOpen;
      if (newIsOpen) {
        this.focus();
      }
      return this;
    }

    /**
     * Gives focus to this contextual link.
     *
     * Also closes + removes focus from every other contextual link.
     *
     * @return {Drupal.contextual.ContextualModelView}
     *   The current contextual model view.
     */
    focus() {
      const { modelId } = this;
      Drupal.contextual.instances.forEach((model) => {
        if (model.modelId !== modelId) {
          model.close().blur();
        }
      });
      window.clearTimeout(this.timer);
      this.hasFocus = true;
      return this;
    }

    /**
     * Removes focus from this contextual link, unless it is open.
     *
     * @return {Drupal.contextual.ContextualModelView}
     *   The current contextual model view.
     */
    blur() {
      if (!this.isOpen) {
        this.hasFocus = false;
      }
      return this;
    }

    /**
     * Closes this contextual link.
     *
     * Does not call blur() because we want to allow a contextual link to have
     * focus, yet be closed for example when hovering.
     *
     * @return {Drupal.contextual.ContextualModelView}
     *   The current contextual model view.
     */
    close() {
      this.isOpen = false;
      return this;
    }

    /**
     * Gets the current focus state.
     *
     * @return {boolean} the focus state.
     */
    get hasFocus() {
      return this._hasFocus;
    }

    /**
     * Sets the current focus state.
     *
     * @param {boolean} value - new focus state
     */
    set hasFocus(value) {
      this._hasFocus = value;
      this.$region.toggleClass('focus', this._hasFocus);
    }

    /**
     * Gets the current open state.
     *
     * @return {boolean} the open state.
     */
    get isOpen() {
      return this._isOpen;
    }

    /**
     * Sets the current open state.
     *
     * @param {boolean} value - new open state
     */
    set isOpen(value) {
      this._isOpen = value;
      // Nested contextual region handling: hide any nested contextual triggers.
      this.$region
        .closest('.contextual-region')
        .find('.contextual .trigger:not(:first)')
        .toggle(!this.isOpen);
    }

    /**
     * Gets the current locked state.
     *
     * @return {boolean} the locked state.
     */
    get isLocked() {
      return this._isLocked;
    }

    /**
     * Sets the current locked state.
     *
     * @param {boolean} value - new locked state
     */
    set isLocked(value) {
      if (value !== this._isLocked) {
        this._isLocked = value;
        this.render();
      }
    }
  };
})(jQuery, Drupal);
