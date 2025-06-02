/**
 * @file
 * Attaches behaviors for the Contextual module.
 */

(function ($, Drupal, drupalSettings, JSON, storage) {
  const options = $.extend(
    drupalSettings.contextual,
    // Merge strings on top of drupalSettings so that they are not mutable.
    {
      strings: {
        open: Drupal.t('Open'),
        close: Drupal.t('Close'),
      },
    },
  );
  // Clear the cached contextual links whenever the current user's set of
  // permissions changes.
  const cachedPermissionsHash = storage.getItem(
    'Drupal.contextual.permissionsHash',
  );
  const { permissionsHash } = drupalSettings.user;
  if (cachedPermissionsHash !== permissionsHash) {
    if (typeof permissionsHash === 'string') {
      Object.keys(storage).forEach((key) => {
        if (key.startsWith('Drupal.contextual.')) {
          storage.removeItem(key);
        }
      });
    }
    storage.setItem('Drupal.contextual.permissionsHash', permissionsHash);
  }

  /**
   * Determines if a contextual link is nested & overlapping, if so: adjusts it.
   *
   * This only deals with two levels of nesting; deeper levels are not touched.
   *
   * @param {jQuery} $contextual
   *   A contextual links placeholder DOM element, containing the actual
   *   contextual links as rendered by the server.
   */
  function adjustIfNestedAndOverlapping($contextual) {
    const $contextuals = $contextual
      // @todo confirm that .closest() is not sufficient
      .parents('.contextual-region')
      .eq(-1)
      .find('.contextual');

    // Early-return when there's no nesting.
    if ($contextuals.length <= 1) {
      return;
    }

    // If the two contextual links overlap, then we move the second one.
    const firstTop = $contextuals.eq(0).offset().top;
    const secondTop = $contextuals.eq(1).offset().top;
    if (firstTop === secondTop) {
      const $nestedContextual = $contextuals.eq(1);

      // Retrieve height of nested contextual link.
      let height = 0;
      const $trigger = $nestedContextual.find('.trigger');
      // Elements with the .visually-hidden class have no dimensions, so this
      // class must be temporarily removed to the calculate the height.
      $trigger.removeClass('visually-hidden');
      height = $nestedContextual.height();
      $trigger.addClass('visually-hidden');

      // Adjust nested contextual link's position.
      $nestedContextual[0].style.top =
        $nestedContextual.position().top + height;
    }
  }

  /**
   * Initializes a contextual link: updates its DOM, sets up model and views.
   *
   * @param {jQuery} $contextual
   *   A contextual links placeholder DOM element, containing the actual
   *   contextual links as rendered by the server.
   * @param {string} html
   *   The server-side rendered HTML for this contextual link.
   */
  function initContextual($contextual, html) {
    const $region = $contextual.closest('.contextual-region');
    const { contextual } = Drupal;

    $contextual
      // Update the placeholder to contain its rendered contextual links.
      .html(html)
      // Use the placeholder as a wrapper with a specific class to provide
      // positioning and behavior attachment context.
      .addClass('contextual')
      // Ensure a trigger element exists before the actual contextual links.
      .prepend(Drupal.theme('contextualTrigger'));

    // Set the destination parameter on each of the contextual links.
    const destination = `destination=${Drupal.encodePath(
      Drupal.url(drupalSettings.path.currentPath + window.location.search),
    )}`;
    $contextual.find('.contextual-links a').each(function () {
      const url = this.getAttribute('href');
      const glue = url.includes('?') ? '&' : '?';
      this.setAttribute('href', url + glue + destination);
    });
    let title = '';
    const $regionHeading = $region.find('h2');
    if ($regionHeading.length) {
      title = $regionHeading[0].textContent.trim();
    }
    options.title = title;
    const contextualModelView = new Drupal.contextual.ContextualModelView(
      $contextual,
      $region,
      options,
    );
    contextual.instances.push(contextualModelView);
    // Fix visual collisions between contextual link triggers.
    adjustIfNestedAndOverlapping($contextual);
  }

  /**
   * Attaches outline behavior for regions associated with contextual links.
   *
   * Events
   *   Contextual triggers an event that can be used by other scripts.
   *   - drupalContextualLinkAdded: Triggered when a contextual link is added.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *  Attaches the outline behavior to the right context.
   */
  Drupal.behaviors.contextual = {
    attach(context) {
      const $context = $(context);

      // Find all contextual links placeholders, if any.
      let $placeholders = $(
        once('contextual-render', '[data-contextual-id]', context),
      );
      if ($placeholders.length === 0) {
        return;
      }

      // Collect the IDs for all contextual links placeholders.
      const ids = [];
      $placeholders.each(function () {
        ids.push({
          id: $(this).attr('data-contextual-id'),
          token: $(this).attr('data-contextual-token'),
        });
      });

      const uncachedIDs = [];
      const uncachedTokens = [];
      ids.forEach((contextualID) => {
        const html = storage.getItem(`Drupal.contextual.${contextualID.id}`);
        if (html?.length) {
          // Initialize after the current execution cycle, to make the AJAX
          // request for retrieving the uncached contextual links as soon as
          // possible, but also to ensure that other Drupal behaviors have had
          // the chance to set up an event listener on the collection
          // Drupal.contextual.collection.
          window.setTimeout(() => {
            initContextual(
              $context
                .find(`[data-contextual-id="${contextualID.id}"]:empty`)
                .eq(0),
              html,
            );
          });
          return;
        }
        uncachedIDs.push(contextualID.id);
        uncachedTokens.push(contextualID.token);
      });

      // Perform an AJAX request to let the server render the contextual links
      // for each of the placeholders.
      if (uncachedIDs.length > 0) {
        $.ajax({
          url: Drupal.url('contextual/render'),
          type: 'POST',
          data: { 'ids[]': uncachedIDs, 'tokens[]': uncachedTokens },
          dataType: 'json',
          success(results) {
            Object.entries(results).forEach(([contextualID, html]) => {
              // Store the metadata.
              storage.setItem(`Drupal.contextual.${contextualID}`, html);
              // If the rendered contextual links are empty, then the current
              // user does not have permission to access the associated links:
              // don't render anything.
              if (html.length > 0) {
                // Update the placeholders to contain its rendered contextual
                // links. Usually there will only be one placeholder, but it's
                // possible for multiple identical placeholders exist on the
                // page (probably because the same content appears more than
                // once).
                $placeholders = $context.find(
                  `[data-contextual-id="${contextualID}"]`,
                );

                // Initialize the contextual links.
                for (let i = 0; i < $placeholders.length; i++) {
                  initContextual($placeholders.eq(i), html);
                }
              }
            });
          },
        });
      }
    },
  };

  /**
   * Namespace for contextual related functionality.
   *
   * @namespace
   *
   * @private
   */
  Drupal.contextual = {
    /**
     * The {@link Drupal.contextual.View} instances associated with each list
     * element of contextual links.
     *
     * @type {Array}
     *
     * @deprecated in drupal:9.4.0 and is removed from drupal:12.0.0. There is no
     *  replacement.
     */
    views: [],

    /**
     * The {@link Drupal.contextual.RegionView} instances associated with each
     * contextual region element.
     *
     * @type {Array}
     *
     * @deprecated in drupal:9.4.0 and is removed from drupal:12.0.0. There is no
     *  replacement.
     */
    regionViews: [],
    instances: new Proxy([], {
      set: function set(obj, prop, value) {
        obj[prop] = value;
        window.dispatchEvent(new Event('contextual-instances-added'));
        return true;
      },
      deleteProperty(target, prop) {
        if (prop in target) {
          delete target[prop];
          window.dispatchEvent(new Event('contextual-instances-removed'));
        }
      },
    }),

    /**
     * Models the state of a contextual link's trigger, list & region.
     */
    ContextualModelView: class {
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
    },
  };

  /**
   * A trigger is an interactive element often bound to a click handler.
   *
   * @return {string}
   *   A string representing a DOM fragment.
   */
  Drupal.theme.contextualTrigger = function () {
    return '<button class="trigger visually-hidden focusable" type="button"></button>';
  };

  /**
   * Bind Ajax contextual links when added.
   *
   * @param {jQuery.Event} event
   *   The `drupalContextualLinkAdded` event.
   * @param {object} data
   *   An object containing the data relevant to the event.
   *
   * @listens event:drupalContextualLinkAdded
   */
  $(document).on('drupalContextualLinkAdded', (event, data) => {
    Drupal.ajax.bindAjaxLinks(data.$el[0]);
  });
})(jQuery, Drupal, drupalSettings, window.JSON, window.sessionStorage);
