/**
 * @file
 * Defines a backwards-compatible shim for jquery.ui.autocomplete.
 */

(($, Drupal) => {
  $.fn.extend({
    autocomplete(...args) {
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
            const [methodName, optionName, optionValue] = args;
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
