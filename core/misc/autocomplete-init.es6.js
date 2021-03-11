(($, Drupal, drupalSettings, DrupalAutocomplete, Popper, once) => {
  Drupal.Autocomplete = {};
  Drupal.Autocomplete.instances = {};
  Drupal.Autocomplete.defaultOptions = {
    // Add jQuery UI classes so the autocomplete is styled the same as its
    // jQuery UI predecessor.
    inputClass: 'ui-autocomplete-input',
    ulClass: 'ui-menu ui-widget ui-widget-content ui-autocomplete ui-front',
    loadingClass: 'ui-autocomplete-loading',
    itemClass: 'ui-menu-item',
    // Do not create an autocomplete-specific live region since
    // #drupal-live-announce will be used.
    createLiveRegion: false,
    displayLabels: false,
  };

  /**
   * Initializes an input to use Drupal Autocomplete.
   *
   * @param {Element} autocompleteInput
   *  The autocomplete input.
   */
  Drupal.Autocomplete.initialize = (autocompleteInput) => {
    const options = Drupal.Autocomplete.defaultOptions || {};
    options.inputAssistiveHint = Drupal.t(
      'When autocomplete results are available use up and down arrows to review and enter to select.  Touch device users, explore by touch or with swipe gestures.',
    );

    // Disable the creation of autocomplete-specific live regions.
    // Drupal.announce() will be used instead.
    options.liveRegion = false;

    /**
     * Formats a message reporting the number of results in a search.
     *
     * This overrides DrupalAutocomplete.suggestionItem()
     *
     * @param {number} count
     *   The number of results.
     *
     * @return {string}
     *   The message to be announced by assistive technology.
     */
    function autocompleteResultsMessage(count) {
      const { maxItems } = this.options;
      if (count === 0) {
        return Drupal.t('No results found');
      }

      const pluralMessage =
        maxItems <= this.totalSuggestions
          ? 'There are at least @count results available. Type additional characters to refine your search.'
          : 'There are @count results available.';
      return Drupal.formatPlural(
        count,
        'There is one result available.',
        pluralMessage,
      );
    }

    /**
     * Formats a message reporting a suggestion has been highlighted.
     *
     * This overrides DrupalAutocomplete.highlightMessage()
     *
     * @param {object} item
     *   The suggestion item being highlighted.
     *
     * @return {string}
     *   The message to be announced by assistive technology.
     */
    function autocompleteHighlightMessage(item) {
      return Drupal.t('@item @count of @total is highlighted', {
        '@item': item.innerText,
        '@count': item.getAttribute('aria-posinset'),
        '@total': this.ul.children.length,
      });
    }

    /**
     * Sends a message to assistive technology.
     *
     * This overrides DrupalAutocomplete.sendToLiveRegion()
     *
     * @param {string} message
     *   The message to be announced.
     */
    function autocompleteSendToLiveRegion(message) {
      Drupal.announce(message, 'assertive');
    }

    // Several DrupalAutocomplete methods are overridden so Drupal.t() and
    // Drupal.announce() can be used without the class itself requiring
    // Drupal.
    const id = autocompleteInput.getAttribute('id');
    Drupal.Autocomplete.instances[id] = new DrupalAutocomplete(
      autocompleteInput,
      options,
    );
    const instance = Drupal.Autocomplete.instances[id];
    instance.resultsMessage = autocompleteResultsMessage;
    instance.sendToLiveRegion = autocompleteSendToLiveRegion;
    instance.highlightMessage = autocompleteHighlightMessage;

    instance.input.addEventListener('autocomplete-destroy', (e) => {
      delete Drupal.Autocomplete.instances[
        e.detail.autocomplete.input.getAttribute('id')
      ];
    });
  };

  /**
   * Provides overrides needed for jQuery UI backwards compatibility.
   *
   * @param {Element} autocompleteInput
   */
  Drupal.Autocomplete.jqueryUiShimInit = (autocompleteInput) => {
    const id = autocompleteInput.getAttribute('id');
    const instance = Drupal.Autocomplete.instances[id];
    const isContentEditable = instance.input.hasAttribute('contenteditable');
    const isMultiline =
      instance.input.tagName === 'TEXTAREA' ||
      (instance.input.tagName !== 'INPUT' && isContentEditable);

    instance.options.isMultiline = isMultiline;

    // jQuery UI allows repeat values in multivalue inputs.
    // If the option is explicitly set to false, that option was set by the form
    // API and should be preserved. Otherwise set to true.
    if (instance.options.allowRepeatValues === null) {
      instance.options.allowRepeatValues = true;
    }

    // If the list was not explicitly appended somewhere else, then it should be
    // appended to body to match jQuery UI markup.
    if (!instance.input.hasAttribute('data-autocomplete-list-appended')) {
      const listBoxId = instance.ul.getAttribute('id');
      const uiFront = $(autocompleteInput).closest('.ui-front, dialog');
      const appendTo =
        uiFront.length > 0 ? uiFront[0] : document.querySelector('body');
      appendTo.appendChild(Drupal.Autocomplete.instances[id].ul);
    }

    // Use Popper to position the list. This isn't needed with
    // Drupal Autocomplete, but must be done when the markup matches jQuery UI.
    Popper.createPopper(instance.input, instance.ul, {
      placement: 'bottom-start',
    });

    /**
     * Alters input keydown behavior to match jQuery UI.
     *
     * @param {Event} e
     *   The keydown event.
     */
    function shimmedInputKeyDown(e) {
      if (
        !['INPUT', 'TEXTAREA'].includes(this.input.tagName) &&
        this.input.hasAttribute('contenteditable')
      ) {
        this.input.value = this.input.textContent;
      }
      const { keyCode } = e;
      if (this.isOpened) {
        if (keyCode === this.keyCode.ESC) {
          this.close();
        }
        if (keyCode === this.keyCode.DOWN || keyCode === this.keyCode.UP) {
          e.preventDefault();
          this.preventCloseOnBlur = true;
          const selector =
            keyCode === this.keyCode.DOWN ? 'li' : 'li:last-child';
          this.highlightItem(this.ul.querySelector(selector));
        }
        if (keyCode === this.keyCode.RETURN) {
          const active = instance.ul.querySelectorAll(
            '.ui-menu-item-wrapper.ui-state-active',
          );
          if (active.length) {
            e.preventDefault();
          }
        }
      }
      if (
        this.input.nodeName === 'INPUT' &&
        !this.isOpened &&
        this.options.list.length > 0 &&
        (keyCode === this.keyCode.DOWN || keyCode === this.keyCode.UP)
      ) {
        e.preventDefault();
        this.suggestionItems = this.options.list;

        this.preventCloseOnBlur = true;

        const typed = this.extractLastInputValue();
        if (!typed && this.options.minChars < 1) {
          this.ul.innerHTML = '';
          this.prepareSuggestionList();
        } else {
          this.displayResults();
        }
        if (this.ul.children.length > 0) {
          this.open();
        }

        if (this.isOpened) {
          const selector =
            keyCode === this.keyCode.DOWN ? 'li' : 'li:last-child';
          this.highlightItem(this.ul.querySelector(selector));
        }
      }
      this.removeAssistiveHint();
    }

    /**
     * Formats an autocomplete suggestion for display in a list item.
     *
     * This overrides DrupalAutocomplete.formatSuggestionItem()
     *
     * @param {object} suggestion
     *   An autocomplete suggestion.
     * @return {string|HTMLElement}
     *   The contents of the list item.
     */
    function autocompleteFormatSuggestionItem(suggestion, li) {
      const propertyToDisplay = this.options.displayLabels ? 'label' : 'value';
      $(li).data('ui-autocomplete-item', suggestion);

      // Wrap the item text in an `<a>`, This tag is not added by default
      // as it's not needed for functionality. However, Claro and Seven
      // both have styles assuming the presence of this tag.
      return `<a tabindex="-1" class="ui-menu-item-wrapper">${suggestion[
        propertyToDisplay
      ].trim()}</a>`;
    }

    instance.formatSuggestionItem = autocompleteFormatSuggestionItem;
    instance.inputKeyDown = shimmedInputKeyDown;

    // Content editable inputs do not
    if (isContentEditable) {
      instance.getValue = function () {
        return this.input.textContent;
      };
      instance.replaceInputValue = function (element) {
        const itemIndex = element
          .closest('[data-drupal-autocomplete-item]')
          .getAttribute('data-drupal-autocomplete-item');
        this.selected = this.suggestions[itemIndex];
        const separator = this.separator();
        if (separator.length > 0) {
          const before = this.previousItems(separator);
          this.input.textContent = `${before}${element.textContent}`;
        } else {
          this.input.textContent = element.textContent;
        }
      };
    }

    /**
     * Replicates a jQuery function of the same name.
     *
     * Used for triggering a close when a click occurs anywhere outside of the
     * autocomplete elements. Drupal Autocomplete has this functionality as
     * well but it's achieved via different events.
     *
     * @param {Event} event
     *   A mousedown event.
     */
    const closeOnClickOutside = (event) => {
      const menuElement = instance.ul;
      const targetInWidget =
        event.target === instance.input ||
        event.target === menuElement ||
        $.contains(menuElement, event.target);
      if (!targetInWidget) {
        instance.close();
      }
    };

    // jQuery UI has an mousedown listener on the list that prevents default.
    instance.ul.addEventListener('mousedown', (e) => {
      e.preventDefault();
    });

    instance.input.addEventListener('autocomplete-open', (e) => {
      document.body.addEventListener('mousedown', closeOnClickOutside);
    });

    instance.input.addEventListener('autocomplete-close', (e) => {
      document.body.removeEventListener('mousedown', closeOnClickOutside);
    });

    instance.input.addEventListener('autocomplete-highlight', (e) => {
      instance.ul
        .querySelectorAll('.ui-menu-item-wrapper.ui-state-active')
        .forEach((element) => {
          element.classList.remove('ui-state-active');
        });
      document.activeElement
        .querySelector('.ui-menu-item-wrapper')
        .classList.add('ui-state-active');
    });

    // jQuery UI autocomplete does not have a wrapper
    $(instance.input).unwrap('[data-drupal-autocomplete-wrapper]');
  };

  /**
   * Attaches the autocomplete behavior to fields configured for autocomplete.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the autocomplete behaviors.
   */
  Drupal.behaviors.autocomplete = {
    attach(context) {
      const $autoCompleteInputs = $(context)
        .find('input.form-autocomplete')
        .once('autocomplete-init');

      once('autocomplete-init', 'input.form-autocomplete').forEach(
        (autocompleteInput) => {
          // The default cardinality of DrupalAutocomplete is 1. Fields in Drupal
          // without explicitly set cardinality should be set to -1, which
          // provides unlimited cardinality.
          if (
            !autocompleteInput.hasAttribute('data-autocomplete-cardinality')
          ) {
            autocompleteInput.setAttribute(
              'data-autocomplete-cardinality',
              '-1',
            );
          }
          Drupal.Autocomplete.initialize(autocompleteInput);
          Drupal.Autocomplete.jqueryUiShimInit(autocompleteInput);
        },
      );
    },
    detach(context, settings, trigger) {
      if (trigger === 'unload') {
        context.querySelectorAll('input.form-autocomplete').forEach((input) => {
          const id = input.getAttribute('id');
          Drupal.Autocomplete.instances[id].destroy();
        });
      }
    },
  };
})(jQuery, Drupal, drupalSettings, DrupalAutocomplete, Popper, once);
