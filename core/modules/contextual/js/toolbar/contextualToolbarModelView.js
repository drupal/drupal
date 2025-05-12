(($, Drupal) => {
  Drupal.contextual.ContextualToolbarModelView = class {
    constructor(options) {
      this.strings = options.strings;
      this.isVisible = false;
      this._contextualCount = Drupal.contextual.instances.count;
      this.tabbingContext = null;
      this._isViewing =
        localStorage.getItem('Drupal.contextualToolbar.isViewing') !== 'false';
      this.$el = options.el;

      window.addEventListener('contextual-instances-added', () =>
        this.lockNewContextualLinks(),
      );
      window.addEventListener('contextual-instances-removed', () => {
        this.contextualCount = Drupal.contextual.instances.count;
      });

      this.$el.on({
        click: () => {
          this.isViewing = !this.isViewing;
        },
        touchend: (event) => {
          event.preventDefault();
          event.target.click();
        },
        'click touchend': () => this.render(),
      });

      $(document).on('keyup', (event) => this.onKeypress(event));
      this.manageTabbing(true);
      this.render();
    }

    /**
     * Responds to esc and tab key press events.
     *
     * @param {jQuery.Event} event
     *   The keypress event.
     */
    onKeypress(event) {
      // The first tab key press is tracked so that an announcement about
      // tabbing constraints can be raised if edit mode is enabled when the page
      // is loaded.
      if (!this.announcedOnce && event.keyCode === 9 && !this.isViewing) {
        this.announceTabbingConstraint();
        // Set announce to true so that this conditional block won't run again.
        this.announcedOnce = true;
      }
      // Respond to the ESC key. Exit out of edit mode.
      if (event.keyCode === 27) {
        this.isViewing = true;
      }
    }

    /**
     * Updates the rendered representation of the current toolbar model view.
     */
    render() {
      this.$el[0].classList.toggle('hidden', this.isVisible);
      const button = this.$el[0].querySelector('button');
      button.classList.toggle('is-active', !this.isViewing);
      button.setAttribute('aria-pressed', !this.isViewing);
      this.contextualCount = Drupal.contextual.instances.count;
    }

    /**
     * Automatically updates visibility of the view/edit mode toggle.
     */
    updateVisibility() {
      this.isVisible = this.get('contextualCount') > 0;
    }

    /**
     * Lock newly added contextual links if edit mode is enabled.
     */
    lockNewContextualLinks() {
      Drupal.contextual.instances.forEach((model) => {
        model.isLocked = !this.isViewing;
      });
      this.contextualCount = Drupal.contextual.instances.count;
    }

    /**
     * Limits tabbing to the contextual links and edit mode toolbar tab.
     *
     * @param {boolean} init - true to initialize tabbing.
     */
    manageTabbing(init = false) {
      let { tabbingContext } = this;
      // Always release an existing tabbing context.
      if (tabbingContext && !init) {
        // Only announce release when the context was active.
        if (tabbingContext.active) {
          Drupal.announce(this.strings.tabbingReleased);
        }
        tabbingContext.release();
        this.tabbingContext = null;
      }
      // Create a new tabbing context when edit mode is enabled.
      if (!this.isViewing) {
        tabbingContext = Drupal.tabbingManager.constrain(
          $('.contextual-toolbar-tab, .contextual'),
        );
        this.tabbingContext = tabbingContext;
        this.announceTabbingConstraint();
        this.announcedOnce = true;
      }
    }

    /**
     * Announces the current tabbing constraint.
     */
    announceTabbingConstraint() {
      const { strings } = this;
      Drupal.announce(
        Drupal.formatString(strings.tabbingConstrained, {
          '@contextualsCount': Drupal.formatPlural(
            Drupal.contextual.instances.length,
            '@count contextual link',
            '@count contextual links',
          ),
        }) + strings.pressEsc,
      );
    }

    /**
     * Gets the current viewing state.
     *
     * @return {boolean} the viewing state.
     */
    get isViewing() {
      return this._isViewing;
    }

    /**
     * Sets the current viewing state.
     *
     * @param {boolean} value - new viewing state
     */
    set isViewing(value) {
      this._isViewing = value;
      localStorage[!value ? 'setItem' : 'removeItem'](
        'Drupal.contextualToolbar.isViewing',
        'false',
      );

      Drupal.contextual.instances.forEach((model) => {
        model.isLocked = !this.isViewing;
      });
      this.manageTabbing();
    }

    /**
     * Gets the current contextual links count.
     *
     * @return {integer} the current contextual links count.
     */
    get contextualCount() {
      return this._contextualCount;
    }

    /**
     * Sets the current contextual links count.
     *
     * @param {integer} value - new contextual links count.
     */
    set contextualCount(value) {
      if (value !== this._contextualCount) {
        this._contextualCount = value;
        this.updateVisibility();
      }
    }
  };
})(jQuery, Drupal);
