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

    instance.options.isMultiline =
      instance.input.tagName === 'TEXTAREA' ||
      (instance.input.tagName !== 'INPUT' && isContentEditable);

    instance.options.itemClass = 'ui-menu-item';

    // jQuery UI allows repeat values in multivalue inputs.
    // If the option is explicitly set to false, that option was set by the form
    // API and should be preserved. Otherwise set to true.
    if (instance.options.allowRepeatValues === null) {
      instance.options.allowRepeatValues = true;
    }

    // If the list was not explicitly appended somewhere else, then it should be
    // appended to match jQuery UI markup.
    if (!instance.input.hasAttribute('data-autocomplete-list-appended')) {
      const listBoxId = instance.ul.getAttribute('id');
      const uiFront = $(autocompleteInput).closest('.ui-front, dialog');

      // If the autocomplete is contained by an element with the class
      // 'ui-front' or a dialog, append the class to that element. Otherwise
      // append it to the document body.
      const appendTo =
        uiFront.length > 0 ? uiFront[0] : document.querySelector('body');
      appendTo.appendChild(instance.ul);
      instance.ul = document.querySelector(`#${listBoxId}`);
    }

    // Position the list directly under the input.
    $(instance.ul).position({
      of: instance.input,
      my: 'left top',
      at: 'left bottom',
    });

    /**
     * Alters input keydown behavior to match jQuery UI.
     *
     * @param {Event} e
     *   The keydown event.
     */
    function shimmedInputKeyDown(e) {
      if (instance.options.isMultiline) {
        this.input.value = this.input.textContent;
      }
      const { keyCode } = e;
      if (this.isOpened) {
        // Escape behavior is identical to A11y_Autocomplete.
        if (keyCode === this.keyCode.ESC) {
          this.close();
        }

        // In jQuery UI, when the input is focused and a list is open,
        // the list can be accessed via up or down arrows. Only the down arrow
        // accomplishes this in A11y_Autocomplete.
        if (keyCode === this.keyCode.DOWN || keyCode === this.keyCode.UP) {
          e.preventDefault();
          this.preventCloseOnBlur = true;

          // Highlight the first or last item, depending on whether the up or
          // down arrow was pressed.
          const selector =
            keyCode === this.keyCode.DOWN ? 'li' : 'li:last-child';
          this.highlightItem(this.ul.querySelector(selector));
        }

        // jQuery UI explicitly cancels 'return' keydown events when an item is
        // highlighted, to prevent form submission. This isn't a concern when
        // shimming A11y_Autocomplete, as the highlighted item also has focus.
        // This event handling is still present so all jQuery UI autocomplete
        // tests will also pass with the shimmed autocomplete.
        if (keyCode === this.keyCode.RETURN) {
          // If this is not null, then an item is highlighted.
          const active = instance.ul.querySelectorAll(
            '.ui-menu-item-wrapper.ui-state-active',
          );
          if (active.length) {
            e.preventDefault();
          }
        }
      }

      // If there is a predefined list, jQuery UI autocomplete allows that list
      // to be opened via the up/down arrow keys. This differs from
      // A11y_Autocomplete, only opens lists based on characters being typed.
      if (
        this.input.nodeName === 'INPUT' &&
        !this.isOpened &&
        this.options.list.length > 0 &&
        (keyCode === this.keyCode.DOWN || keyCode === this.keyCode.UP)
      ) {
        e.preventDefault();

        // This property is always set to true when arrow keys are used to move
        // into an item list. This prevents the default behavior of the list
        // closing when the input is blurred.
        this.preventCloseOnBlur = true;

        // See if anything has been typed into the input.
        const typed = this.extractLastInputValue();

        // In instances where nothing is typed and there is no character
        // minimum, the list must be opened using something other than
        // displayResults(), as that method requires input to work.
        if (!typed && this.options.minChars < 1) {
          // Reset the item list to avoid duplication when prepareItemList() is
          // called.
          this.ul.innerHTML = '';

          // Move the predefined list into suggestionItems, so they can be
          // processed by prepareSuggestionList().
          this.suggestionItems = this.options.list;

          // Convert the predefined list into markup.
          this.prepareSuggestionList();

          // Make the markup visible.
          this.open();
        } else {
          this.displayResults();
        }

        // If the arrow key press resulted in the opening of a list, then
        // highlight the first/last item depending on whether the up/down key
        // was pressed.
        if (this.isOpened) {
          const selector =
            keyCode === this.keyCode.DOWN ? 'li' : 'li:last-child';
          this.highlightItem(this.ul.querySelector(selector));
        }
      }

      // This call is also made in the A11y_Autocomplete version of this
      // method. It removes some general assistive hints after the first keydown
      // event to avoid unnecessary repetition.
      this.removeAssistiveHint();
    }
    instance.inputKeyDown = shimmedInputKeyDown;

    /**
     * Creates a suggestion list based on a typed value.
     *
     * The majority of this function is identical to A11y_Autocomplete
     * prepareSuggestionList(). It is changed at the end to be compatible with
     * jQuery UI extension points.
     *
     * @param {string} typed
     *   The typed value querying autocomplete.
     */
    function autocompletePrepareSuggestionList(typed) {
      this.normalizeSuggestionItems();
      if (typed) {
        this.suggestions = this.suggestionItems.filter((item) =>
          this.filterResults(item, typed),
        );
      } else {
        this.suggestions = this.suggestionItems;
      }
      if (this.options.sort !== false) {
        this.sortSuggestions();
      }
      this.totalSuggestions = this.suggestions.length;
      this.suggestions = this.suggestions.slice(
        0,
        parseInt(this.options.maxItems, 10),
      );

      this.triggerEvent('autocomplete-response', {
        list: this.suggestions,
      });

      // Everything up until this point is identical to A11y_Autocomplete
      // prepareSuggestionList(). This call to _renderMenu is provided instead
      // of the forEach loop that creates the item list so jQuery UI's
      // extension points are supported.
      this.ul.innerHTML = '';
      this._renderMenu(this.ul, this.suggestions);

      // Add the list attributes needed for functionality that would have
      // been added in suggestionItem() were that function not skipped in order
      // to support extension points.
      this.ul.querySelectorAll('li').forEach((li, index) => {
        if (this.options.itemClass.length > 0) {
          this.options.itemClass
            .split(' ')
            .forEach((className) => li.classList.add(className));
        }
        li.setAttribute('role', 'option');
        li.setAttribute('tabindex', '-1');
        li.setAttribute('id', `suggestion-${this.count}-${index}`);
        li.setAttribute('data-drupal-autocomplete-item', index);
        li.setAttribute('aria-posinset', index + 1);
        li.setAttribute('aria-selected', 'false');
        li.onblur = (e) => this.blurHandler(e);
        li.querySelector('a').classList.add('ui-menu-item-wrapper');
      });
    }
    instance.prepareSuggestionList = autocompletePrepareSuggestionList;

    if (!instance.hasOwnProperty('_renderMenu')) {
      // eslint-disable-next-line func-names
      instance._renderMenu = function (ul, items) {
        const that = this;
        // eslint-disable-next-line func-names
        $.each(items, function (index, item) {
          that._renderItemData(ul, item);
        });
      };
    }
    if (!instance.hasOwnProperty('_renderItemData')) {
      // eslint-disable-next-line func-names
      instance._renderItemData = function (ul, item) {
        return this._renderItem(ul, item).data('ui-autocomplete-item', item);
      };
    }

    if (!instance.hasOwnProperty('_renderItem')) {
      // eslint-disable-next-line func-names
      instance._renderItem = function (ul, item) {
        return $('<li>').append($('<a>').html(item.label)).appendTo(ul);
      };
    }

    // Elements with the contenteditable attribute require different logic than
    // the default behavior which expects a text input.
    if (isContentEditable) {
      // eslint-disable-next-line func-names
      instance.getValue = function () {
        return this.input.textContent;
      };

      // The replaceInputValue method assumes the autocomplete input has a
      // `value` property. This is overridden here when there's a need to
      // accommodate contentEditable elements that don't use that property.
      // eslint-disable-next-line func-names
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
    // jQuery UI will close the autocomplete results on any mousedown that lands
    // outside of the autocomplete widget.
    instance.input.addEventListener('autocomplete-open', () => {
      document.body.addEventListener('mousedown', closeOnClickOutside);
    });
    instance.input.addEventListener('autocomplete-close', () => {
      document.body.removeEventListener('mousedown', closeOnClickOutside);
    });

    // jQuery UI has a mousedown listener on the list that prevents default.
    instance.ul.addEventListener('mousedown', (e) => {
      e.preventDefault();
    });

    // If the input receives focus, remove the 'ui-state-active' class from all
    // result items.
    instance.input.addEventListener('focus', () => {
      instance.ul
        .querySelectorAll('.ui-menu-item-wrapper.ui-state-active')
        .forEach((element) => {
          element.classList.remove('ui-state-active');
        });
    });

    // When a result item is highlighted, jQuery UI adds a 'ui-state-active'
    // class to it.
    instance.input.addEventListener('autocomplete-highlight', () => {
      instance.ul
        .querySelectorAll('.ui-menu-item-wrapper.ui-state-active')
        .forEach((element) => {
          element.classList.remove('ui-state-active');
        });
      document.activeElement
        .querySelector('.ui-menu-item-wrapper')
        .classList.add('ui-state-active');
    });

    // jQuery UI autocomplete does not have a wrapper, so remove the wrapper
    // added by A11y_Autocomplete.
    $(instance.input).unwrap('[data-drupal-autocomplete-wrapper]');
    $(instance.input).data('ui-autocomplete', instance);
  };

  // This fully replaces jQuery UI's autocomplete() function. This reproduces
  // the API surface of jQuery UI autocomplete, but uses A11y_Autocomplete for
  // the functionality.
  $.fn.extend({
    autocomplete(...args) {
      Drupal.deprecationError({
        message:
          'The autocomplete() function is deprecated in drupal:9.2.0 and is removed from drupal:10.0.0. Use the API provided by core/a11y_autocomplete instead. See https://www.drupal.org/node/3083715',
      });
      const id = this.attr('id');

      // Some jQuery UI options can be directly mapped to A11y_Autocomplete
      // options..
      const optionMapping = {
        autoFocus: 'autoFocus',
        classes: null,
        delay: 'searchDelay',
        disabled: 'disabled',
        minLength: 'minChars',
        position: null,
        source: null,
      };

      // If args[0] is a string, the autocomplete instance is already
      // initialized and the string represents a method the autocomplete should
      // execute.
      if (typeof args[0] === 'string') {
        const instance = Drupal.Autocomplete.instances[id];
        const method = args[0];

        switch (method) {
          case 'widget':
            // The widget option returns the autocomplete item list.
            return $(instance.ul);
          case 'instance':
            // This is the one method that will not return an exact replica
            // of what jQuery UI would return, as jQuery UI autocomplete
            // returns an object that is very jQuery-integrated. The properties
            // that do not have non-jQuery equivalents are null.
            // eslint-disable-next-line no-case-declarations
            const instanceToReturn = {
              document: $(document),
              element: $(instance.input),
              menu: {
                element: $(instance.ul),
              },
              liveRegion: $(instance.liveRegion),
              classesElementLookup: null,
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

            // Add a warning for instance properties that can't be provided via
            // shim.
            [
              'bindings',
              'eventNamespace',
              'classesElementLookup',
              'focusable',
              'hoverable',
              'uuid',
              'valueMethod',
            ].forEach((property) => {
              Object.defineProperty(instanceToReturn, property, {
                get() {
                  // eslint-disable-next-line no-console
                  return console.warn(
                    `The ${property} property is not supported beginning with 9.2, as jQuery UI Autocomplete is no longer part of core. See https://www.drupal.org/node/3083715`,
                  );
                },
              });
            });

            return instanceToReturn;
          case 'disable':
            this.autocomplete('option', 'disabled', true);
            break;
          case 'enable':
            this.autocomplete('option', 'disabled', false);
            break;
          case 'search':
            // The 'search' method performs a search as if the input received
            // input events.

            // A11y_Autocomplete expects the input to be focused when a search
            // occurs, even if it's programmatically triggered.
            instance.input.focus();

            // If the args[1] argument is present, it will be the search term.
            if (typeof args[1] === 'string') {
              // The input's value property must always be set as it's used
              // internally by A11y_Autocomplete.
              [, instance.input.value] = args;

              // For contenteditable elements, the textContent property must
              // also match the provided value for it to be visible.
              if (instance.input.hasAttribute('contenteditable')) {
                [, instance.input.textContent] = args;
              }
            } else if (instance.input.hasAttribute('contenteditable')) {
              // The input's value property must always be set as it's used
              // internally by A11y_Autocomplete.
              instance.input.value = instance.input.textContent;
            }

            // If there is no typed value and no minimum character limit, the
            // 'search' option will display the results of a predefined list,
            // when such a list is available.
            if (
              instance.input.value.length === 0 &&
              instance.options.minChars === 0 &&
              instance.options.list.length > 0
            ) {
              instance.suggestionItems = instance.options.list;
              instance.prepareSuggestionList();
              if (instance.ul.children.length === 0) {
                instance.close();
              } else {
                instance.open();
              }
            } else {
              // If args[1] isn't a string, just trigger a search using whatever
              // is currently in the input.
              instance.doSearch($.Event('keydown'));
            }
            break;
          case 'option':
            // If args[2] doesn't exist, and args[1] is an object, treat each
            // args[1] object property as an individual autocomplete option that
            // should be set to the corresponding value.
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
                  // This option accepts an object keyed by the default class
                  // of the element receiving the new class, and the value is
                  // the class/classes that should replace that default.
                  Object.keys(optionValue).forEach((key) => {
                    if (
                      key === 'ui-autocomplete' ||
                      key === 'ui-autocomplete-input'
                    ) {
                      const element =
                        key === 'ui-autocomplete'
                          ? instance.ul
                          : instance.input;
                      optionValue[key].split(' ').forEach((className) => {
                        element.classList.add(className);
                      });
                      // Remove the default class.
                      element.classList.remove(key);
                    }
                  });
                  break;
                case 'classes.ui-autocomplete':
                  // Add the new class(es).
                  optionValue.split(' ').forEach((className) => {
                    instance.ul.classList.add(className);
                  });
                  // Remove the default class.
                  instance.ul.classList.remove('ui-autocomplete');
                  break;
                case 'classes.ui-autocomplete-input':
                  // Add the new class(es).
                  optionValue.split(' ').forEach((className) => {
                    instance.input.classList.add(className);
                  });
                  // Remove the default class.
                  instance.input.classList.remove('ui-autocomplete-input');
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
                    // The default value of `of:` is the input.
                    of: instance.input,
                    ...optionValue,
                  });
                  break;
                case 'source':
                  // In jQuery UI autocomplete, 'source' can be one of three
                  // types:
                  // - Function: a callback function that overrides the default
                  //   autocomplete search functionality.
                  // - String: Either a JSON formatted list of items, or a URL
                  //   to an endpoint that returns items.
                  // - Array: An array of list items. Can be an array of stings
                  //   or of objects with `label` and `value` properties.
                  if (typeof optionValue === 'function') {
                    /**
                     * A callback function used by the 'source' function override.
                     *
                     * @param {String[]|Object[]} newList
                     *   The data that will be suggested.
                     */
                    // eslint-disable-next-line func-names
                    const overriddenResponse = function (newList) {
                      instance.options.list = newList;
                      instance.suggestionItems = instance.options.list;
                      instance.displayResults();
                    };
                    // eslint-disable-next-line func-names
                    instance.doSearch = function () {
                      // This overrides autocomplete search functionality with
                      // the logic provided in the 'optionValue' function.
                      // Argument 1 is a 'request' object, with a single 'term'
                      // property that matches the current search string.
                      // Argument 2 is a 'response' callback that expects a single
                      // argument: the data to suggest to the user.
                      optionValue(
                        { term: instance.extractLastInputValue() },
                        overriddenResponse,
                      );
                    };
                  } else if (typeof optionValue === 'string') {
                    // When the 'source' option is a string, it can either be a
                    // URL to an endpoint, or a JavaScript array of items. This
                    // try/catch is implemented to distinguish between the two. If
                    // parsing the string as JSON results in an error, it is
                    // assumed the string is a URL.
                    // Unlike jQuery UI autocomplete, which uses the 'source'
                    // option for both URLs and predefined lists,
                    // A11y_Autocomplete stores these as individual 'path' and
                    // 'list' options.
                    try {
                      // The contents of JSON.parse are assigned to a variable
                      // instead of directly to instance.options.list so the
                      // exception can be caught before any option values are
                      // changed.
                      // eslint-disable-next-line no-unused-vars
                      const list = JSON.parse(optionValue);
                      instance.options.list = list;
                    } catch (e) {
                      instance.options.path = optionValue;
                    }
                  } else {
                    instance.options.list = optionValue;
                  }
                  break;
                default:
                  // If the option is an event name, then the optionValue is
                  // a handler for that event.
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

                  // Some jQuery UI autocomplete options have 1:1 equivalents.
                  // Those cases are identified and mapped here.
                  if (optionMapping.hasOwnProperty(optionName)) {
                    instance.options[optionMapping[optionName]] = optionValue;
                    // Duplicate the option with jQuery UI naming for BC.
                    instance.options[optionName] = optionValue;
                  }
                  break;
              }
            } else if (typeof args[1] === 'string') {
              // If args[1] is a string, it is the name of an option. Return the
              // value of that option.
              return instance.options(args[1]);
            }
            break;
          default:
            // Some jQuery UI methods have identically A11y_Autocomplete methods
            // that provide the same functionality and can simply be called.
            if (typeof instance[method] === 'function') {
              instance[method]();
            }
            break;
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

  $.ui.autocomplete = () => {
    console.warn(
      '$.ui.autocomplete no longer exists due to its removal in Drupal 9.2.0. Existing uses of $().autocomplete() will continue to work. See https://www.drupal.org/node/3083715',
    );
  };
})(jQuery, Drupal);
