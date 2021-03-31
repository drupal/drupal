/**
 * @file
 * Defines a backwards-compatible shim for jquery.ui.autocomplete.
 */

(($, Drupal) => {
  /**
   * Provides overrides needed for jQuery UIs backwards compatibility.
   *
   * These overrides can only be applied after autocomplete initializes.
   *
   * @param {Element} autocompleteInput
   *   The initialized autocomplete input.
   */
  Drupal.Autocomplete.jqueryUiShimInit = (autocompleteInput) => {
    const id = autocompleteInput.getAttribute('id');
    const instance = Drupal.Autocomplete.instances[id];
    const isContentEditable = instance.input.hasAttribute('contenteditable');
    const isMultiline =
      instance.input.tagName === 'TEXTAREA' ||
      (instance.input.tagName !== 'INPUT' && isContentEditable);

    instance.options.isMultiline = isMultiline;
    instance.options.itemClass = 'ui-menu-item';

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
      Drupal.Autocomplete.instances[id].ul = document.querySelector(
        `#${listBoxId}`,
      );
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
     * This overrides A11yAutocomplete.formatSuggestionItem()
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
  $.fn.extend({
    autocomplete(...args) {
      Drupal.deprecationError({
        message:
          'The autocomplete() function is deprecated in drupal:9.2.0 and is removed from drupal:10.0.0. Use the API provided by core/a11y_autocomplete instead. See https://www.drupal.org/node/3083715',
      });
      const id = this.attr('id');

      // Some jQuery UI options can be directly mapped to Drupal autocomplete.
      const optionMapping = {
        autoFocus: 'autoFocus',
        classes: null,
        delay: 'searchDelay',
        disabled: 'disabled',
        minLength: 'minChars',
        position: null,
        source: null,
      };

      if (typeof args[0] === 'string') {
        const instance = Drupal.Autocomplete.instances[id];
        const method = args[0];

        switch (method) {
          case 'search':
            instance.input.focus();

            if (typeof args[1] === 'string') {
              if (instance.input.hasAttribute('contenteditable')) {
                [, instance.input.textContent] = args;
              }
              [, instance.input.value] = args;
            } else if (instance.input.hasAttribute('contenteditable')) {
              instance.input.value = instance.input.textContent;
            }

            if (
              instance.input.value.length === 0 &&
              instance.options.minChars === 0
            ) {
              instance.suggestionItems = instance.options.list;
              instance.prepareSuggestionList();
              if (instance.ul.children.length === 0) {
                instance.close();
              } else {
                instance.open();
              }

              window.clearTimeout(instance.timeOutId);
              // Delay the results announcement by 1400 milliseconds. This prevents
              // unnecessary calls when a user is typing quickly, and avoids the results
              // announcement being cut short by the screenreader stating the just-typed
              // character.
              instance.timeOutId = setTimeout(
                () =>
                  instance.sendToLiveRegion(
                    instance.resultsMessage(instance.ul.children.length),
                  ),
                1400,
              );
            } else {
              instance.doSearch($.Event('keydown'));
            }
            break;
          case 'widget':
            return $(instance.ul);
          case 'instance':
            return {
              document: $(document),
              element: $(instance.input),
              menu: {
                element: $(instance.ul),
              },
              liveRegion: $(instance.liveRegion),
              bindings: null,
              classesElementLookup: null,
              eventNamespace: null,
              focusable: null,
              hoverable: null,
              isMultiLine: instance.options.isMultiLine,
              isNewMenu: null,
              options: instance.options,
              source: null,
              uuid: null,
              valueMethod: null,
              window,
            };
          case 'close':
            instance.close();
            break;
          case 'disable':
            this.autocomplete('option', 'disabled', true);
            break;
          case 'enable':
            this.autocomplete('option', 'disabled', false);
            break;

          default:
            if (typeof instance[method] === 'function') {
              instance[method]();
            }
            break;
        }

        if (method === 'option') {
          if (typeof args[2] === 'undefined' && args[1] === 'object') {
            // Individually set each option specified in the object.
            Object.keys(args[1]).forEach((key) => {
              this.autocomplete('option', key, args[1][key]);
            });
          }

          // If args[2] has a value, then this is setting an option.
          if (typeof args[2] !== 'undefined' && typeof args[1] === 'string') {
            const [, optionName, optionValue] = args;
            const listBoxId = instance.ul.getAttribute('id');

            switch (optionName) {
              case 'appendTo':
                // The option value can be a selector string, element, or jQuery
                // object. Convert to element.
                // eslint-disable-next-line no-case-declarations
                let appendTo = null;
                if (typeof optionValue === 'string') {
                  appendTo = document.querySelector(optionValue);
                } else if (optionValue instanceof jQuery) {
                  appendTo = optionValue.length > 0 ? optionValue[0] : null;
                } else {
                  appendTo = optionValue;
                }
                if (!appendTo) {
                  const closestUiFront = $(instance.input).closest(
                    '.ui-front, dialog',
                  );
                  if (closestUiFront.length > 0) {
                    [appendTo] = closestUiFront;
                  }
                }

                if (appendTo) {
                  if (!appendTo.contains(instance.ul)) {
                    appendTo.appendChild(instance.ul);
                  }
                  instance.ul = appendTo.querySelector(`#${listBoxId}`);
                }

                // Add attribute that flags the shim initializer to skip the
                // default behavior of appending the list to `document.body`.
                instance.input.setAttribute(
                  'data-autocomplete-list-appended',
                  true,
                );
                break;
              case 'classes':
                Object.keys(optionValue).forEach((key) => {
                  if (
                    key === 'ui-autocomplete' ||
                    key === 'ui-autocomplete-input'
                  ) {
                    const element =
                      key === 'ui-autocomplete' ? instance.ul : instance.input;
                    optionValue[key].split(' ').forEach((className) => {
                      element.classList.add(className);
                    });
                    element.classList.remove(key);
                  }
                });
                break;
              case 'classes.ui-autocomplete':
                optionValue.split(' ').forEach((className) => {
                  instance.ul.classList.add(className);
                });
                break;
              case 'classes.ui-autocomplete-input':
                optionValue.split(' ').forEach((className) => {
                  instance.input.classList.add(className);
                });
                break;
              case 'disabled':
                instance.options.disabled = optionValue;
                $(instance.ul).toggleClass(
                  'ui-autocomplete-disabled',
                  optionValue,
                );
                break;
              case 'position':
                $(instance.ul).position({
                  of: instance.input,
                  ...optionValue,
                });
                break;
              case 'source':
                if (typeof optionValue === 'function') {
                  // eslint-disable-next-line func-names
                  const overriddenResponse = function (newList) {
                    instance.options.list = newList;
                    instance.suggestionItems = instance.options.list;
                    instance.displayResults();
                  };
                  // eslint-disable-next-line func-names
                  instance.doSearch = function () {
                    optionValue(
                      { term: instance.extractLastInputValue() },
                      overriddenResponse,
                    );
                  };
                } else if (typeof optionValue === 'string') {
                  try {
                    const list = JSON.parse(optionValue);
                    instance.options.list = list;
                  } catch (e) {
                    instance.options.path = optionValue;
                  }
                } else {
                  Drupal.Autocomplete.instances[id].options.list = optionValue;
                }
                break;
              default:
                if (
                  [
                    'change',
                    'close',
                    'create',
                    'focus',
                    'open',
                    'response',
                    'search',
                    'select',
                  ].includes(optionName)
                ) {
                  this.on(`autocomplete${optionName}`, optionValue);
                }
                if (optionMapping.hasOwnProperty(optionName)) {
                  instance.options[optionMapping[optionName]] = optionValue;
                  // Duplicate the option with jQuery UI naming for BC.
                  instance.options[optionName] = optionValue;
                }

                break;
            }
          } else if (typeof args[1] === 'string') {
            return instance.options(args[1]);
          }
        }
      } else {
        // This condition means argument 1 was not a string. This means a new
        // autocomplete instance should be initialized.
        Drupal.Autocomplete.initialize(this[0]);
        Drupal.Autocomplete.jqueryUiShimInit(this[0]);

        // If argument 1 is an object, they are options that should be set on
        // the newly created autocomplete.
        if (typeof args[0] === 'object') {
          Object.keys(args[0]).forEach((key) => {
            this.autocomplete('option', key, args[0][key]);
          });
        }
      }

      return this;
    },
  });
})(jQuery, Drupal);
