/**
 * @file
 * Provides admin UI for the CKEditor 5.
 */

((Drupal, drupalSettings, $, JSON, once, Sortable) => {
  const toolbarHelp = [
    {
      message: Drupal.t(
        "The toolbar buttons that don't fit the user's browser window width will be grouped in a dropdown. If multiple toolbar rows are preferred, those can be configured by adding an explicit wrapping breakpoint wherever you want to start a new row.",
        null,
        {
          context:
            'CKEditor 5 toolbar help text, default, no explicit wrapping breakpoint',
        },
      ),
      button: 'wrapping',
      condition: false,
    },
    {
      message: Drupal.t(
        'You have configured a multi-row toolbar by using an explicit wrapping breakpoint. This may not work well in narrow browser windows. To use automatic grouping, remove any of these divider buttons.',
        null,
        {
          context:
            'CKEditor 5 toolbar help text, with explicit wrapping breakpoint',
        },
      ),
      button: 'wrapping',
      condition: true,
    },
  ];

  /**
   * Allows attaching listeners to a value.
   *
   * @type {Observable}
   */
  const Observable = class {
    /**
     * Creates new Observable with a value.
     *
     * @param {*} value
     *   The value to be observed.
     */
    constructor(value) {
      this._listeners = [];
      this._value = value;
    }

    /**
     * Notifies subscribers about new value.
     */
    notify() {
      this._listeners.forEach((listener) => listener(this._value));
    }

    /**
     * Subscribes to be notified for changes.
     *
     * @param {Function} listener
     *   The function to be called when a new value is set.
     */
    subscribe(listener) {
      this._listeners.push(listener);
    }

    /**
     * The value of the observable.
     *
     * @return {*}
     *   The current value.
     */
    get value() {
      return this._value;
    }

    /**
     * Sets the value of the observable and notifies subscribers.
     *
     * @param {*} val
     *   The new value of the observable.
     */
    set value(val) {
      if (val !== this._value) {
        this._value = val;
        this.notify();
      }
    }
  };

  /**
   * Gets selected buttons.
   *
   * @param {Array} selected
   *   The selected buttons retrieved from state.
   * @param {Array} dividers
   *   The available dividers.
   * @param {Array} available
   *   The available buttons.
   * @return {Array}
   *   An array containing selected buttons.
   */
  const getSelectedButtons = (selected, dividers, available) => {
    return selected.map((id) => ({
      ...[...dividers, ...available].find((button) => button.id === id),
    }));
  };

  /**
   * Updates selected buttons to the textarea.
   *
   * @param {Array} selection
   *   The current selection.
   * @param {Element} textarea
   *   The textarea element.
   */
  const updateSelectedButtons = (selection, textarea) => {
    const newValue = JSON.stringify(selection);

    const priorValue = textarea.innerHTML;
    textarea.value = newValue;
    textarea.innerHTML = newValue;

    // The textarea is programmatically updated, so no native JavaScript
    // event is triggered. Event listeners need to be aware of this config
    // update, so a custom event is dispatched immediately after the
    // config update.
    textarea.dispatchEvent(
      new CustomEvent('change', {
        detail: {
          priorValue,
        },
      }),
    );
  };

  /**
   * Function to add button to selected buttons.
   *
   * @param {Observable} selection
   *   The currently selected buttons.
   * @param {Element} element
   *   The element which is being added.
   * @param {function} announceChange
   *   Function to call to announce the change.
   */
  const addToSelectedButtons = (selection, element, announceChange) => {
    const list = [...selection.value];
    list.push(element.dataset.id);
    selection.value = list;

    if (announceChange) {
      setTimeout(() => {
        announceChange(element.dataset.label);
      });
    }
  };

  /**
   * Function to remove button from selected buttons.
   *
   * @param {Observable} selection
   *   The currently selected buttons.
   * @param {Element} element
   *   The element which is being removed.
   * @param {function} announceChange
   *   Function to call to announce the change.
   */
  const removeFromSelectedButtons = (selection, element, announceChange) => {
    const list = [...selection.value];
    const index = Array.from(element.parentElement.children).findIndex(
      (child) => {
        return child === element;
      },
    );
    list.splice(index, 1);
    selection.value = list;

    if (announceChange) {
      setTimeout(() => {
        announceChange(element.dataset.label);
      });
    }
  };

  /**
   * Moves element within active buttons.
   *
   * @param {Observable} selection
   *   The currently selected buttons.
   * @param {Element} element
   *   The element being moved.
   * @param {Number} dir
   *   The direction which the element is being moved.
   */
  const moveWithinSelectedButtons = (selection, element, dir) => {
    const list = [...selection.value];
    const index = Array.from(element.parentElement.children).findIndex(
      (child) => {
        return child === element;
      },
    );
    // If moving up, check it is not the first, else check it is not the last.
    const condition = dir < 0 ? index > 0 : index < list.length - 1;
    if (condition) {
      list.splice(index + dir, 0, list.splice(index, 1)[0]);
      selection.value = list;
    }
  };

  /**
   * Copies element to active buttons.
   *
   * @param {Observable} selection
   *   The currently selected buttons.
   * @param {Element} element
   *   The element to be copied.
   * @param {Function} announceChange
   *   A function to call to announce the change.
   */
  const copyToActiveButtons = (selection, element, announceChange) => {
    const list = [...selection.value];
    list.push(element.dataset.id);
    selection.value = list;

    setTimeout(() => {
      if (announceChange) {
        announceChange(element.dataset.label);
      }
    });
  };

  /**
   * Renders the CKEditor 5 button admin UI.
   *
   * @param {Element} root
   *   The element where the admin UI should be rendered.
   * @param {Observable} selectedButtons
   *   An Observable object containing selected buttons.
   * @param {Array} availableButtons
   *   An array containing available buttons.
   * @param {Array} dividerButtons
   *   An array containing available divider buttons.
   */
  const render = (root, selectedButtons, availableButtons, dividerButtons) => {
    const toolbarHelpText = toolbarHelp
      .filter(
        (helpItem) =>
          selectedButtons.value.includes(helpItem.button) ===
          helpItem.condition,
      )
      .map((helpItem) => helpItem.message);
    root.innerHTML = Drupal.theme.ckeditor5Admin({
      availableButtons: Drupal.theme.ckeditor5AvailableButtons({
        buttons: availableButtons.filter(
          (button) => !selectedButtons.value.includes(button.id),
        ),
      }),
      dividerButtons: Drupal.theme.ckeditor5DividerButtons({
        buttons: dividerButtons,
      }),
      activeToolbar: Drupal.theme.ckeditor5SelectedButtons({
        buttons: getSelectedButtons(
          selectedButtons.value,
          dividerButtons,
          availableButtons,
        ),
      }),
      helpMessage: toolbarHelpText,
    });

    // Create sortable groups for available buttons, current toolbar items,
    // and dividers.
    new Sortable(
      root.querySelector(
        '[data-button-list="ckeditor5-toolbar-active-buttons"]',
      ),
      {
        group: { name: 'toolbar', put: ['divider', 'available'] },
        sort: true,
        store: {
          set: (sortable) => {
            selectedButtons.value = sortable.toArray();
          },
        },
      },
    );
    const toolbarAvailableButtons = new Sortable(
      root.querySelector(
        '[data-button-list="ckeditor5-toolbar-available-buttons"]',
      ),
      {
        group: { name: 'available', put: ['toolbar'] },
        sort: false,
        onAdd: (event) => {
          // If the moved item is a divider, it should not be added to
          // the available buttons list.
          if (
            dividerButtons.find(
              (dividerButton) => dividerButton.id === event.item.dataset.id,
            )
          ) {
            const { newIndex } = event;
            setTimeout(() => {
              // Remove the divider button from the available buttons
              // list. Draggable reassesses the lists during each drag
              // event, so the DOM removal should not be disruptive.
              document
                .querySelectorAll('.ckeditor5-toolbar-available__buttons li')
                [newIndex].remove();
            });
          }
        },
      },
    );
    new Sortable(
      root.querySelector(
        '[data-button-list="ckeditor5-toolbar-divider-buttons"]',
      ),
      {
        group: { name: 'divider', put: false, pull: 'clone', sort: 'false' },
      },
    );

    root
      .querySelectorAll('[data-drupal-selector="ckeditor5-toolbar-button"]')
      .forEach((element) => {
        const expandButton = (event) => {
          event.currentTarget
            .querySelectorAll('.ckeditor5-toolbar-button')
            .forEach((buttonElement) => {
              buttonElement.setAttribute('data-expanded', true);
            });
        };
        const retractButton = (event) => {
          event.currentTarget
            .querySelectorAll('.ckeditor5-toolbar-button')
            .forEach((buttonElement) => {
              buttonElement.setAttribute('data-expanded', false);
            });
        };
        element.addEventListener('mouseenter', expandButton);
        element.addEventListener('focus', expandButton);
        element.addEventListener('mouseleave', retractButton);
        element.addEventListener('blur', retractButton);

        element.addEventListener('keyup', (event) => {
          // Keys supported by the admin UI. Depending on the element that is
          // triggering the event, the event could trigger changes in the state.
          // Changes to the state trigger re-rendering of the admin UI, which
          // means that for consistent navigation, each action modifying state
          // needs to set focus back on the button that is being moved. The state
          // change also triggers an AJAX request which re-renders parts of the
          // form, and moves the focus to the triggering form element, meaning
          // that focus needs to be set back on the button again.
          const supportedKeys = [
            'ArrowLeft',
            'ArrowRight',
            'ArrowUp',
            'ArrowDown',
          ];
          const dir = document.documentElement.dir;
          if (supportedKeys.includes(event.key)) {
            if (event.currentTarget.dataset.divider.toLowerCase() === 'true') {
              switch (event.key) {
                case 'ArrowDown': {
                  const announceChange = (name) => {
                    Drupal.announce(
                      Drupal.t(
                        'Button @name has been copied to the active toolbar.',
                        { '@name': name },
                      ),
                    );
                  };
                  copyToActiveButtons(
                    selectedButtons,
                    event.currentTarget,
                    announceChange,
                  );
                  // Focus the last button since new button is always added to the
                  // end of the list.
                  root
                    .querySelector(
                      '[data-button-list="ckeditor5-toolbar-active-buttons"] li:last-child',
                    )
                    .focus();
                  break;
                }
              }
            } else if (
              selectedButtons.value.includes(event.currentTarget.dataset.id)
            ) {
              const index = Array.from(
                element.parentElement.children,
              ).findIndex((child) => {
                return child === element;
              });
              switch (event.key) {
                case 'ArrowLeft': {
                  const leftOffset = dir === 'ltr' ? -1 : 1;
                  moveWithinSelectedButtons(
                    selectedButtons,
                    event.currentTarget,
                    leftOffset,
                  );
                  // Move focus to left or right from the current index depending
                  // on current language direction. Use index instead of the
                  // data-id because dividers don't have a unique ID.
                  root
                    .querySelectorAll(
                      '[data-button-list="ckeditor5-toolbar-active-buttons"] li',
                    )
                    [index + leftOffset].focus();
                  break;
                }
                case 'ArrowRight': {
                  const rightOffset = dir === 'ltr' ? 1 : -1;
                  moveWithinSelectedButtons(
                    selectedButtons,
                    event.currentTarget,
                    rightOffset,
                  );
                  // Move focus to right or left from the current index depending
                  // on current language direction. Use index instead of the
                  // data-id because dividers don't have a unique ID.
                  root
                    .querySelectorAll(
                      '[data-button-list="ckeditor5-toolbar-active-buttons"] li',
                    )
                    [index + rightOffset].focus();
                  break;
                }
                case 'ArrowUp': {
                  const announceChange = (name) => {
                    Drupal.announce(
                      Drupal.t(
                        'Button @name has been removed from the active toolbar.',
                        { '@name': name },
                      ),
                    );
                  };
                  removeFromSelectedButtons(
                    selectedButtons,
                    event.currentTarget,
                    announceChange,
                  );
                  // Focus only if the button wasn't a divider because dividers
                  // are simply removed from the active buttons instead of moving
                  // to another list.
                  if (
                    !dividerButtons.find(
                      (dividerButton) =>
                        event.currentTarget.dataset.id === dividerButton.id,
                    )
                  ) {
                    // Focus button based on the data-id attribute from the
                    // available buttons list.
                    root
                      .querySelector(
                        `[data-button-list="ckeditor5-toolbar-available-buttons"] [data-id="${event.currentTarget.dataset.id}"]`,
                      )
                      .focus();
                  }
                  break;
                }
              }
            } else if (
              toolbarAvailableButtons
                .toArray()
                .includes(event.currentTarget.dataset.id)
            ) {
              switch (event.key) {
                case 'ArrowDown': {
                  const announceChange = (name) => {
                    Drupal.announce(
                      Drupal.t(
                        'Button @name has been moved to the active toolbar.',
                        { '@name': name },
                      ),
                    );
                  };
                  addToSelectedButtons(
                    selectedButtons,
                    event.currentTarget,
                    announceChange,
                  );
                  // Focus the last button since new button is always added to the
                  // end of the list.
                  root
                    .querySelector(
                      '[data-button-list="ckeditor5-toolbar-active-buttons"] li:last-child',
                    )
                    .focus();
                  break;
                }
              }
            }
          }
        });
      });
  };

  /**
   * Attach CKEditor 5 admin UI.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches admin app to edit the CKEditor 5 toolbar.
   * @prop {Drupal~behaviorDetach} detach
   *   Detaches admin app from the CKEditor 5 configuration form on 'unload'.
   */
  Drupal.behaviors.ckeditor5Admin = {
    attach(context) {
      once('ckeditor5-admin-toolbar', '#ckeditor5-toolbar-app').forEach(
        (container) => {
          const selectedTextarea = context.querySelector(
            '#ckeditor5-toolbar-buttons-selected',
          );
          const available = Object.entries(
            JSON.parse(
              context.querySelector('#ckeditor5-toolbar-buttons-available')
                .innerHTML,
            ),
          ).map(([name, attrs]) => ({ name, id: name, ...attrs }));
          const dividers = [
            {
              id: 'divider',
              name: '|',
              label: Drupal.t('Divider'),
            },
            {
              id: 'wrapping',
              name: '-',
              label: Drupal.t('Wrapping'),
            },
          ];

          // Selected is used for managing the state. Sortable is handling updates
          // to the state when the system is operated by mouse. There are
          // functions making direct modifications to the state when system is
          // operated by keyboard.
          const selected = new Observable(
            JSON.parse(selectedTextarea.innerHTML).map((name) => {
              return [...dividers, ...available].find((button) => {
                return button.name === name;
              }).id;
            }),
          );

          const mapSelection = (selection) => {
            return selection.map((id) => {
              return [...dividers, ...available].find((button) => {
                return button.id === id;
              }).name;
            });
          };
          // Whenever the state is changed, update the textarea with the changes.
          // This will also trigger re-render of the admin UI to reinitialize the
          // Sortable state.
          selected.subscribe((selection) => {
            updateSelectedButtons(mapSelection(selection), selectedTextarea);
            render(container, selected, available, dividers);
          });

          [
            context.querySelector('#ckeditor5-toolbar-buttons-available'),
            context.querySelector('[class*="editor-settings-toolbar-items"]'),
          ]
            .filter((el) => el)
            .forEach((el) => {
              el.classList.add('visually-hidden');
            });

          render(container, selected, available, dividers);
        },
      );
      // Safari's focus outlines take into account absolute positioned elements.
      // When a toolbar option is blurred, the portion of the focus outline
      // surrounding the absolutely positioned tooltip does not go away. To
      // prevent keyboard navigation users from seeing outline artifacts for
      // every option they've tabbed through, we provide a keydown listener
      // that can catch blur-causing events before the blur happens. If the
      // tooltip is hidden before the blur event, the outline will disappear
      // correctly.
      once(
        'safari-focus-fix',
        document.querySelectorAll('.ckeditor5-toolbar-item'),
      ).forEach((item) => {
        item.addEventListener('keydown', (e) => {
          const keyCodeDirections = {
            9: 'tab',
            37: 'left',
            38: 'up',
            39: 'right',
            40: 'down',
          };
          if (
            ['tab', 'left', 'up', 'right', 'down'].includes(
              keyCodeDirections[e.keyCode],
            )
          ) {
            let hideTip = false;
            const isActive = e.target.closest(
              '[data-button-list="ckeditor5-toolbar-active__buttons"]',
            );
            if (isActive) {
              if (
                ['tab', 'left', 'up', 'right'].includes(
                  keyCodeDirections[e.keyCode],
                )
              ) {
                hideTip = true;
              }
            } else if (['tab', 'down'].includes(keyCodeDirections[e.keyCode])) {
              hideTip = true;
            }
            if (hideTip) {
              e.target
                .querySelector('[data-expanded]')
                .setAttribute('data-expanded', 'false');
            }
          }
        });
      });

      /**
       * Updates the UI state info in the form's 'data-drupal-ui-state' attribute.
       *
       * @param {object} states
       *   An object with one or more items with the structure { ui-property: stored-value }
       */
      const updateUiStateStorage = (states) => {
        const form = document.querySelector(
          '#filter-format-edit-form, #filter-format-add-form',
        );

        // Get the current stored UI state as an object.
        const currentStates = form.hasAttribute('data-drupal-ui-state')
          ? JSON.parse(form.getAttribute('data-drupal-ui-state'))
          : {};

        // Store the updated UI state object as an object literal in the parent
        // form's 'data-drupal-ui-state' attribute.
        form.setAttribute(
          'data-drupal-ui-state',
          JSON.stringify({ ...currentStates, ...states }),
        );
      };

      /**
       * Gets a stored UI state property.
       *
       * @param {string} property
       *   The UI state property to retrieve the value of.
       *
       * @return {string|null}
       *   When present, the stored value of the property.
       */
      const getUiStateStorage = (property) => {
        const form = document.querySelector(
          '#filter-format-edit-form, #filter-format-add-form',
        );

        if (form === null) {
          return;
        }

        // Parse the object literal stored in the form's 'data-drupal-ui-state'
        // attribute and return the value of the object property that matches
        // the 'property' argument.
        return form.hasAttribute('data-drupal-ui-state')
          ? JSON.parse(form.getAttribute('data-drupal-ui-state'))[property]
          : null;
      };

      // Add an attribute to the parent form for storing UI states, so this
      // information can be retrieved after AJAX rebuilds.
      once(
        'ui-state-storage',
        document.querySelector(
          '#filter-format-edit-form, #filter-format-add-form',
        ),
      ).forEach((form) => {
        form.setAttribute('data-drupal-ui-state', JSON.stringify({}));
      });

      /**
       * Maintains the active vertical tab after AJAX rebuild.
       *
       * @param {Element} verticalTabs
       *   The vertical tabs element.
       */
      const maintainActiveVerticalTab = (verticalTabs) => {
        const id = verticalTabs.id;

        // If the UI state storage has an active tab, click that tab.
        const activeTab = getUiStateStorage(`${id}-active-tab`);
        if (activeTab) {
          setTimeout(() => {
            document.querySelector(activeTab).click();
          });
        }

        // Add click listener that adds any tab click into UI storage.
        verticalTabs.querySelectorAll('.vertical-tabs__menu').forEach((tab) => {
          tab.addEventListener('click', (e) => {
            const state = {};
            const href = e.target
              .closest('[href]')
              .getAttribute('href')
              .split('--')[0];
            state[`${id}-active-tab`] = `#${id} [href^='${href}']`;
            updateUiStateStorage(state);
          });
        });
      };

      once(
        'plugin-settings',
        document.querySelector('#plugin-settings-wrapper'),
      ).forEach(maintainActiveVerticalTab);
      once(
        'filter-settings',
        document.querySelector('#filter-settings-wrapper'),
      ).forEach(maintainActiveVerticalTab);

      // Add listeners to maintain focus after AJAX rebuilds.
      const selectedButtons = document.querySelector(
        '#ckeditor5-toolbar-buttons-selected',
      );

      once('textarea-listener', selectedButtons).forEach((textarea) => {
        textarea.addEventListener('change', (e) => {
          const buttonName = document.activeElement.getAttribute('data-id');
          if (!buttonName) {
            // If there is no 'data-id' attribute, then the config
            // is happening via mouse.
            return;
          }
          let focusSelector = '';

          // Divider elements are treated differently as there can be multiple
          // elements with the same button name.
          if (['divider', 'wrapping'].includes(buttonName)) {
            const oldConfig = JSON.parse(e.detail.priorValue);
            const newConfig = JSON.parse(e.target.innerHTML);

            // If the divider is being removed from active buttons, it does not
            // 'move' anywhere. Move focus to the prior active button
            if (oldConfig.length > newConfig.length) {
              for (let item = 0; item < newConfig.length; item++) {
                if (newConfig[item] !== oldConfig[item]) {
                  focusSelector = `[data-button-list="ckeditor5-toolbar-active-buttons"] li:nth-child(${Math.min(
                    item - 1,
                    0,
                  )})`;
                  break;
                }
              }
            } else if (oldConfig.length < newConfig.length) {
              // If the divider is being added, it will be the last active item.
              focusSelector =
                '[data-button-list="ckeditor5-toolbar-active-buttons"] li:last-child';
            } else {
              // When moving a dividers position within the active buttons.
              document
                .querySelectorAll(
                  `[data-button-list="ckeditor5-toolbar-active-buttons"] [data-id='${buttonName}']`,
                )
                .forEach((divider, index) => {
                  if (divider === document.activeElement) {
                    focusSelector = `${buttonName}|${index}`;
                  }
                });
            }
          } else {
            focusSelector = `[data-id='${buttonName}']`;
          }

          // Store the focus selector in an attribute on the form itself, which
          // will not be overwritten after the AJAX rebuild. This makes it
          // the value available to the textarea focus listener that is
          // triggered after the AJAX rebuild.
          updateUiStateStorage({ focusSelector });
        });

        textarea.addEventListener('focus', () => {
          // The selector that should receive focus is stored in the parent
          // form element. Move focus to that selector.
          const focusSelector = getUiStateStorage('focusSelector');

          if (focusSelector) {
            // If focusSelector includes '|', it is a separator that is being
            // moved within the active button list. Different logic us used to
            // determine focus since there can be multiple separators of the
            // same type within the active buttons list.
            if (focusSelector.includes('|')) {
              const [buttonName, count] = focusSelector.split('|');
              document
                .querySelectorAll(
                  `[data-button-list="ckeditor5-toolbar-active-buttons"] [data-id='${buttonName}']`,
                )
                .forEach((item, index) => {
                  if (index === parseInt(count, 10)) {
                    item.focus();
                  }
                });
            } else {
              const toFocus = document.querySelector(focusSelector);
              if (toFocus) {
                toFocus.focus();
              }
            }
          }
        });
      });
    },
  };

  /**
   * Theme function for CKEditor 5 selected buttons.
   *
   * @param {Object} options
   *   An object containing options.
   * @param {Array} options.buttons
   *   An array of selected buttons.
   * @return {string}
   *   The selected buttons markup.
   *
   * @internal
   */
  Drupal.theme.ckeditor5SelectedButtons = ({ buttons }) => {
    return `
      <ul class="ckeditor5-toolbar-tray ckeditor5-toolbar-active__buttons" data-button-list="ckeditor5-toolbar-active-buttons" role="listbox" aria-orientation="horizontal" aria-labelledby="ckeditor5-toolbar-active-buttons-label">
        ${buttons
          .map((button) =>
            Drupal.theme.ckeditor5Button({ button, listType: 'active' }),
          )
          .join('')}
      </ul>
    `;
  };

  /**
   * Theme function for CKEditor 5 divider buttons.
   *
   * @param {Object} options
   *   An object containing options.
   * @param {Array} options.buttons
   *   An array of divider buttons.
   * @return {string}
   *   The CKEditor 5 divider buttons markup.
   *
   * @internal
   */
  Drupal.theme.ckeditor5DividerButtons = ({ buttons }) => {
    return `
      <ul class="ckeditor5-toolbar-tray ckeditor5-toolbar-divider__buttons" data-button-list="ckeditor5-toolbar-divider-buttons" role="listbox" aria-orientation="horizontal" aria-labelledby="ckeditor5-toolbar-divider-buttons-label">
        ${buttons
          .map((button) =>
            Drupal.theme.ckeditor5Button({ button, listType: 'divider' }),
          )
          .join('')}
      </ul>
    `;
  };

  /**
   * Theme function for CKEditor 5 available buttons.
   *
   * @param {Object} options
   *   An object containing options.
   * @param {Array} options.buttons
   *   An array of available buttons.
   * @return {string}
   *   The CKEditor 5 available buttons markup.
   *
   * @internal
   */
  Drupal.theme.ckeditor5AvailableButtons = ({ buttons }) => {
    return `
      <ul class="ckeditor5-toolbar-tray ckeditor5-toolbar-available__buttons" data-button-list="ckeditor5-toolbar-available-buttons" role="listbox" aria-orientation="horizontal" aria-labelledby="ckeditor5-toolbar-available-buttons-label">
        ${buttons
          .map((button) =>
            Drupal.theme.ckeditor5Button({ button, listType: 'available' }),
          )
          .join('')}
      </ul>
    `;
  };

  /**
   * Theme function for CKEditor 5 buttons.
   *
   * @param {Object} options
   *  An object containing options.
   * @param {Object} options.button
   *   An object containing button options.
   * @param {String} options.button.label
   *   Button label.
   * @param {String} options.button.id
   *   Button id.
   * @param {String} options.listType
   *   The type of the list.
   * @return {string}
   *   The CKEditor 5 buttons markup.
   *
   * @internal
   */
  Drupal.theme.ckeditor5Button = ({ button: { label, id }, listType }) => {
    const visuallyHiddenLabel = Drupal.t(`@listType button @label`, {
      '@listType': listType !== 'divider' ? listType : 'available',
      '@label': label,
    });
    return `
      <li class="ckeditor5-toolbar-item ckeditor5-toolbar-item-${id}" role="option" tabindex="0" data-drupal-selector="ckeditor5-toolbar-button" data-id="${id}" data-label="${label}" data-divider="${
      listType === 'divider'
    }">
        <span class="ckeditor5-toolbar-button ckeditor5-toolbar-button-${id}">
          <span class="visually-hidden">${visuallyHiddenLabel}</span>
        </span>
        <span class="ckeditor5-toolbar-tooltip" aria-hidden="true">${label}</span>
      </li>
    `;
  };

  /**
   * Theme function for CKEditor 5 admin UI.
   *
   * @param {Object} options
   *   An object containing options.
   * @param {String} options.availableButtons
   *   Markup for available buttons.
   * @param {String} options.dividerButtons
   *   Markup for divider buttons.
   * @param {String} options.activeToolbar
   *   Markup for active toolbar.
   * @param {Array} options.helpMessage
   *   An array of help messages.
   * @return {string}
   *   The CKEditor 5 admin UI markup.
   *
   * @internal
   */
  Drupal.theme.ckeditor5Admin = ({
    availableButtons,
    dividerButtons,
    activeToolbar,
    helpMessage,
  }) => {
    return `
    <div aria-live="polite" data-drupal-selector="ckeditor5-admin-help-message">
      <p>${helpMessage.join('</p><p>')}</p>
    </div>
    <div class="ckeditor5-toolbar-disabled">
      <div class="ckeditor5-toolbar-available">
        <label id="ckeditor5-toolbar-available-buttons-label">${Drupal.t(
          'Available buttons',
        )}</label>
        ${availableButtons}
      </div>
      <div class="ckeditor5-toolbar-divider">
        <label id="ckeditor5-toolbar-divider-buttons-label">${Drupal.t(
          'Button divider',
        )}</label>
        ${dividerButtons}
      </div>
    </div>
    <div class="ckeditor5-toolbar-active">
      <label id="ckeditor5-toolbar-active-buttons-label">${Drupal.t(
        'Active toolbar',
      )}</label>
      ${activeToolbar}
    </div>
    `;
  };

  // Make a copy of the default filterStatus attach behaviors so it can be
  // called within this module's override of it.
  const originalFilterStatusAttach = Drupal.behaviors.filterStatus.attach;

  // Overrides the default filterStatus to provided functionality needs
  // specific to CKEditor 5.
  Drupal.behaviors.filterStatus.attach = (context, settings) => {
    const filterStatusCheckboxes = document.querySelectorAll(
      '#filters-status-wrapper input.form-checkbox',
    );

    // CKEditor 5 has uses cases that require updating the filter settings via
    // AJAX. When this happens, the checkboxes that enable filters must be
    // reprocessed by the filterStatus behavior. For this to occur:
    // 1. 'filter-status' must be removed from the element's once registry so
    //    the process can run again and take into account any filter settings
    //    elements that have been added or removed from the DOM.
    //    @see core/assets/vendor/once/once.js
    once.remove('filter-status', filterStatusCheckboxes);

    // 2. Any listeners to the 'click.filterUpdate' event should be removed so
    //    they do not conflict with event listeners that are added as part of
    //    the AJAX refresh.
    $(filterStatusCheckboxes).off('click.filterUpdate');

    // Call the original behavior.
    originalFilterStatusAttach(context, settings);
  };

  // Activates otherwise-inactive tabs that have form elements with validation
  // errors.
  // @todo Remove when https://www.drupal.org/project/drupal/issues/2911932 lands.
  Drupal.behaviors.tabErrorsVisible = {
    attach(context) {
      context.querySelectorAll('details .form-item .error').forEach((item) => {
        const details = item.closest('details');
        if (details.style.display === 'none') {
          const tabSelect = document.querySelector(`[href='#${details.id}']`);
          if (tabSelect) {
            tabSelect.click();
          }
        }
      });
    },
  };
})(Drupal, drupalSettings, jQuery, JSON, once, Sortable);
