/**
 * A standalone autocomplete, optimized for accessibility and querying remote
 * sources.
 *
 * @example <caption>Initialization</caption>
 * // Given an input to receive autocomplete functionality.
 * <input id="an-input" />
 * const input = document.querySelector('#an-input');
 *
 * // Initialize the autocomplete with a fixed list of items
 * const autocompleteInstanceFixedList = new A11y_Autocomplete(input, {
 *   list: ['first item', 'second item', 'third item'],
 * });
 *
 * // Or initialize the autocomplete to query items from an endpoint
 * const autocompleteInstanceFixedList = new A11y_Autocomplete(input, {
 *   path: 'https://url.with.query.results',
 * });
 *
 * // When the autocomplete is initialized, markup is added.
 * <!-- The input is wrapped in a div, making is possible to position the results list with CSS. -->
 * <div data-drupal-autocomplete-wrapper>
 *   <-- Several attributes are added to the input, used for accessibility and being identifiable by JavaScript. -->
 *   <input id="an-input" aria-autocomplete="list" autocomplete="off" data-drupal-autocomplete-input aria-owns="autocomplete-listbox-0" role="combobox" aria-expanded="false" aria-describedby="assistive-hint-0">
 *     <!-- This span provides assitive technology such as screenreaders with additional information when the input is focused. -->
 *     <span class="visually-hidden" id="assistive-hint-0">Type 2 or more characters for results. When autocomplete results are available use up and down arrows to review and enter to select. Touch device users, explore by touch or with swipe gestures.</span>
 *     <!-- This is the <ul> that will list the query results when characters are typed in the input -->
 *     <ul role="listbox" data-drupal-autocomplete-list="" id="autocomplete-listbox-0" hidden=""></ul>
 *     <!-- This is a live region, used for conveying the results of interactions to assistive technology, such as the number of results available after typing. -->
 *     <span data-drupal-autocomplete-live-region="" aria-live="assertive"></span>
 * </div>
 *
 * @example <caption>Setting Options</caption>
 * // Options can be set in three ways, listed from highest precedence to lowest:
 *
 * // 1. An object literal in the input's `data-autocomplete` attribute with the format {camelCaseOptionName: value}.
 * <input data-autocomplete="{maxItems: 10, path:'http://path-to-results'}" />
 *
 * // 2. Via the data-autocomplete-(hyphen delimited option name) attribute.
 * <input data-autocomplete-max-items="10" data-autocomplete-path="http://path-to-results" />
 *
 * // 3. Via the options argument when initializing a new instance
 * new A11y_Autocomplete(input, {maxItems: 10, , path:'http://path-to-results'})
 *
 * @param {HTMLElement} input
 *   The element to be used as an autocomplete.
 * @param {Object} options
 *  Autocomplete options, these will override the default options.
 * @param {string} [options.path=''] - This should be a URL that returns search
 *  results. By default, when an autocomplete search begins it queries
 *  `(the value of 'path')?q=(value typed in the autocomplete input)`. How the
 *  autocomplete interacts with `path` can be customized by overriding [queryUrl()]{@link A11yAutocomplete#queryUrl}.
 * @param {Object[]|string[]} [options.list=[]] - A predefined list of autocomplete options. This can be an array of strings or objects with
 * `label:` and `value:`properties. If this array is not empty, this property will take
 * precedence over searching the remote resource specified in `path`. {@link options.path}
 * @param {string|null} [options.allowRepeatValues=null] - If `true`,
 *  autocomplete results can include items already included in the field. A null
 *  value functions the same as false, but a null value can be used to determine
 *  if this value was explicitly set or using defaults.
 * @param {Boolean} [options.autoFocus=false] - When `true`, the first result is
 *  focused as soon as a list of results becomes visible.
 * @param {string} [options.separatorChar=','] - The character used to separate
 *  multiple values in the same form.
 * @param {string} [options.firstCharacterDenylist=','] - Any characters in this
 *  string will not be incorporated in a search as the first character of a
 *  query. Typically, this string should at least include the value of
 *  `separatorChar`.
 * @param {Number} [options.minChars=1] - Minimum number of characters that must
 *  be typed before displaying autocomplete results.
 * @param {Number} [options.maxItems=20] - The maximum number of results
 *  displayed.
 * @param {Number} [options.cardinality=1] - The number of values the input can
 *  reference, where multiple values are separated by the character specified in
 *  the `separatorChar` option. Set to `-1` for unlimited cardinality.
 * @param {Boolean} [options.sort=true] - When `true` the results are sorted
 *  prior to display. The default sorting behavior is alphabetical, and can be
 *  customized by overriding [sortSuggestions()]{@link A11yAutocomplete#sortSuggestions}.
 *  #### `displayLabels` (default: `true`)
 * @param {Boolean} [options.displayLabels=true] - When `true`, and when query
 *  results are provided as an object with `label:` and `value:` properties, the
 *  suggestion list will display the item `label:`, but when selected the input
 *  will receive the value set in `value:`.
 * @param {Boolean} [options.disabled=false] - When `true`, input events on
 *  the input will not trigger searches.
 * @param {string} [options.inputClass=''] - Additional classes to be added to
 *  the autocomplete input.
 * @param {string} [options.loadingClass=''] - Additional classes to be added to
 *  the autocomplete input while waiting for a query to return results.
 * @param {string} [options.ulClass=''] - Additional classes to be added to
 *  the results list container `<ul>`
 * @param {string} [options.itemClass=''] - Additional classes that can be
 *  added to each `<li>` item in the results list.
 * @param {Boolean} [options.createLiveRegion=true] - When `true`, initialization
 *  includes adding a live region specifically that will convey autocomplete activity
 *  to assistive technology. This is typically only set to `false` if a site has
 *  a centralized live region for assistive technology announcements.
 * @param {Number} [options.listZindex=1] - The CSS z-index of the results list.
 * @param {Number} [options.searchDelay=300] - Time to in milliseconds after
 *  input activity before an autocomplete search is triggered. Typically used to
 *  prevent unnecessary searches before typing is completed.
 * @param {string} [options.inputAssistiveHint='When autocomplete results are available use up and down arrows to review and enter to select. Touch device users, explore by touch or with swipe gestures.'] -
 *  Message conveyed to assistive technology when the input is focused.
 * @param {string} [options.minCharAssistiveHint='Type @count or more characters for results'] -
 *  When `minChars` is greater than one, this is appended to
 *  `messages.inputAssistiveHint` so users are aware how many characters are
 *  needed to trigger a search.  `@count` is replaced with the value of`minChars`.
 * @param {string} [options.noResultsAssistiveHint='No results found'] - Message
 *  conveyed to assistive technology when the query returns no results.
 * @param {string} [options.moreThanMaxResultsAssistiveHint='There are at least @count results available. Type additional characters to refine your search.'] -
 *  Message conveyed to assistive technology when the number of results exceeds
 *  the maximum amount set via the `maxItems` option. `@count` is  replaced with
 *  the value of `maxItems`.
 * @param {string} [options.someResultsAssistiveHint='There are @count results available.'] -
 *  Message conveyed to assistive technology when the number of results exceeds
 *  one and does not exceed the maximum amount set via the `maxItems` option.
 *  `@count` is replaced with the number of results returned.
 * @param {string} [options.oneResultAssistiveHint='There is one result available.'] -
 *  Message conveyed to assistive technology when there is one result.
 *
 * @return {A11yAutocomplete}
 *   Class to manage an input's autocomplete functionality.
 *
 * @fires A11yAutocomplete#autocomplete-change
 * @fires A11yAutocomplete#autocomplete-close
 * @fires A11yAutocomplete#autocomplete-created
 * @fires A11yAutocomplete#autocomplete-destroy
 * @fires A11yAutocomplete#autocomplete-highlight
 * @fires A11yAutocomplete#autocomplete-open
 * @fires A11yAutocomplete#autocomplete-pre-search
 * @fires A11yAutocomplete#autocomplete-response
 * @fires A11yAutocomplete#autocomplete-select
 * @fires A11yAutocomplete#autocomplete-selection-added
 */
class A11yAutocomplete {
  /**
   * Construct a new A11yAutocomplete class.
   */
  constructor(input, options = {}) {
    this.keyCode = Object.freeze({
      TAB: 9,
      RETURN: 13,
      ESC: 27,
      SPACE: 32,
      PAGEUP: 33,
      PAGEDOWN: 34,
      END: 35,
      HOME: 36,
      LEFT: 37,
      UP: 38,
      RIGHT: 39,
      DOWN: 40,
    });

    this.input = input;

    this.count = document.querySelectorAll('[data-autocomplete-input]').length;
    this.listboxId = `autocomplete-listbox-${this.count}`;

    const defaultOptions = {
      autoFocus: false,
      firstCharacterDenylist: ',',
      minChars: 1,
      maxItems: 20,
      sort: false,
      path: '',
      displayLabels: true,
      disabled: false,
      list: [],
      cardinality: 1,
      inputClass: '',
      ulClass: '',
      itemClass: '',
      loadingClass: '',
      separatorChar: ',',
      createLiveRegion: true,
      listZindex: 100,
      allowRepeatValues: null,
      searchDelay: 300,
      minCharAssistiveHint: 'Type @count or more characters for results',
      inputAssistiveHint:
        'When autocomplete results are available use up and down arrows to review and enter to select. Touch device users, explore by touch or with swipe gestures.',
      noResultsAssistiveHint: 'No results found',
      moreThanMaxResultsAssistiveHint:
        'There are at least @count results available. Type additional characters to refine your search.',
      someResultsAssistiveHint: 'There are @count results available.',
      oneResultAssistiveHint: 'There is one result available.',
      highlightedAssistiveHint: '@selectedItem @position of @count is highlighted',
    };

    this.options = {
      ...defaultOptions,
      ...options,
      ...this.attributesToOptions(),
    };

    // Preset lists provided as strings should be converted to options.
    if (typeof this.options.list === 'string') {
      this.options.list = JSON.parse(this.options.list);
    }

    this.selected = null;
    this.preventCloseOnBlur = false;
    this.isOpened = false;
    this.cache = [];
    this.suggestionItems = [];
    this.hasAnnouncedOnce = false;
    this.announceTimeOutId = null;
    this.searchTimeOutId = null;
    this.totalSuggestions = 0;

    // Create a div that will wrap the input and suggestion list.
    this.wrapper = document.createElement('div');
    this.implementWrapper();

    this.inputDescribedBy = this.input.getAttribute('aria-describedby');
    this.inputHintRead = false;
    this.implementInput();

    // Create the list that will display suggestions.
    this.ul = document.createElement('ul');
    this.implementList();
    this.appendList();

    // When applicable, create a live region for announcing suggestion results
    // to assistive technology.
    this.liveRegion = null;
    this.implementLiveRegion();

    // Events to add.
    this.events = {
      input: {
        input: (e) => this.inputListener(e),
        blur: (e) => this.blurHandler(e),
        keydown: (e) => this.inputKeyDown(e),
      },
      ul: {
        mousedown: (e) => e.preventDefault(),
        click: (e) => this.itemClick(e),
        keydown: (e) => this.listKeyDown(e),
        blur: (e) => this.blurHandler(e),
        focus: (e) => this.listFocus(e),
      },
    };

    Object.keys(this.events).forEach((elementName) => {
      Object.keys(this.events[elementName]).forEach((eventName) => {
        this[elementName].addEventListener(eventName, this.events[elementName][eventName]);
      });
    });

    /**
     * Fires after initialization and markup additions.
     *
     * @event A11yAutocomplete#autocomplete-created
     * @property {Class} autocomplete - The autocomplete instance.
     */
    this.triggerEvent('autocomplete-created');
  }

  /**
   * Sets attributes to the wrapper and inserts it in the DOM.
   */
  implementWrapper() {
    this.wrapper.setAttribute('data-autocomplete-wrapper', '');
    this.input.parentNode.appendChild(this.wrapper);
    this.wrapper.appendChild(this.input);
  }

  /**
   * Sets attributes to the input and inserts it in the DOM.
   */
  implementInput() {
    // Add attributes to the input.
    this.input.setAttribute('aria-autocomplete', 'list');
    this.input.setAttribute('autocomplete', 'off');
    this.input.setAttribute('data-autocomplete-input', '');
    this.input.setAttribute('aria-owns', this.listboxId);
    this.input.setAttribute('role', 'combobox');
    this.input.setAttribute('aria-expanded', 'false');
    if (this.options.inputClass.length > 0) {
      this.options.inputClass.split(' ').forEach((className) => this.input.classList.add(className));
    }
    if (!this.input.hasAttribute('id')) {
      this.input.setAttribute('id', `autocomplete-input-${this.count}`);
    }

    const description = document.createElement('span');
    description.textContent = this.minCharsMessage() + this.options.inputAssistiveHint;
    description.classList.add('visually-hidden');

    // If the autocomplete input has an pre-existing 'aria-describedby', append
    // autocomplete-specific descriptions to that existing element. Otherwise,
    // create a new element with those descriptions and create a new
    // 'aria-describedby' to associate it with the input.
    if (this.inputDescribedBy) {
      // This is content that is appended to an existing description. It's also
      // content that only needs to be conveyed once. Add an attribute that
      // allows this content to be targeted for removal after it is read once.
      description.setAttribute('data-autocomplete-assistive-hint', `${this.count}`);
      document.querySelector(`[id="${this.inputDescribedBy}"]`).appendChild(description);
    } else {
      description.setAttribute('id', `assistive-hint-${this.count}`);
      this.input.setAttribute('aria-describedby', `assistive-hint-${this.count}`);
      this.wrapper.appendChild(description);
    }
  }

  /**
   * Inserts list into DOM.
   */
  appendList() {
    this.input.parentNode.appendChild(this.ul);
  }

  /**
   * Sets attributes to the results list and inserts it in the DOM.
   */
  implementList() {
    this.ul.setAttribute('role', 'listbox');
    this.ul.setAttribute('data-autocomplete-item-list', '');
    this.ul.setAttribute('id', this.listboxId);
    this.ul.setAttribute('hidden', '');
    if (this.options.ulClass.length > 0) {
      this.options.ulClass.split(' ').forEach((className) => this.ul.classList.add(className));
    }
  }

  /**
   * Creates a live region for reporting status to assistive technology.
   */
  implementLiveRegion() {
    // If the liveRegion option is set to true, create a new live region and
    // insert it in the autocomplete wrapper.
    if (this.options.createLiveRegion === true) {
      this.liveRegion = document.createElement('span');
      this.liveRegion.setAttribute('data-autocomplete-live-region', '');
      this.liveRegion.setAttribute('aria-live', 'assertive');
      this.input.parentNode.appendChild(this.liveRegion);
    }

    // If the liveRegion option is a string, it should be a selector for an
    // already-existing live region.
    if (typeof this.options.liveRegion === 'string') {
      this.liveRegion = document.querySelector(this.options.liveRegion);
    }
  }

  /**
   * Converts data-autocomplete* attributes into options.
   *
   * @return {object} an autocomplete options object.
   * @private
   */
  attributesToOptions() {
    const options = {};
    // Any options provided in the `data-autocomplete` attribute will take
    // precedence over those specified in `data-autocomplete-(x)`.
    const dataAutocompleteAttributeOptions = this.input.getAttribute('data-autocomplete')
      ? JSON.parse(this.input.getAttribute('data-autocomplete'))
      : {};

    // Loop through all of the input's attributes. Any attributes beginning with
    // `data-autocomplete` will be added to an options object.
    for (let i = 0; i < this.input.attributes.length; i++) {
      if (
        this.input.attributes[i].nodeName.includes('data-autocomplete') &&
        this.input.attributes[i].nodeName !== 'data-autocomplete'
      ) {
        // Convert the data attribute name to camel case for use in the options
        // object.
        let optionName = this.input.attributes[i].nodeName
          .replace('data-autocomplete-', '')
          .split('-')
          .map((w) => w.charAt(0).toUpperCase() + w.slice(1))
          .join('');
        optionName = optionName.charAt(0).toLowerCase() + optionName.slice(1);
        const value = this.input.attributes[i].nodeValue;
        if (['true', 'false'].includes(value)) {
          options[optionName] = value === 'true';
        } else {
          options[optionName] = value;
        }
      }
    }

    return { ...options, ...dataAutocompleteAttributeOptions };
  }

  /**
   * Handles blur events.
   *
   * @param {Event} e
   *   The blur event.
   */
  blurHandler(e) {
    // If an element is blurred, cancel any pending screenreader announcements
    // as they would be specific to an element no longer in focus.
    window.clearTimeout(this.announceTimeOutId);
    if (this.preventCloseOnBlur) {
      this.preventCloseOnBlur = false;
      e.preventDefault();
    } else {
      /**
       * Fires after an item is blurred and another item hasn't been highlighted.
       *
       * @event A11yAutocomplete#autocomplete-change
       * @property {Class} autocomplete - The autocomplete instance.
       */
      this.triggerEvent('autocomplete-change');
      this.close();
    }
  }

  /**
   * Removes one-time-only assistive hints.
   */
  removeAssistiveHint() {
    if (!this.inputHintRead) {
      if (this.inputDescribedBy) {
        const appendedHint = document.querySelector(`[data-autocomplete-assistive-hint="${this.count}"]`);
        appendedHint.parentNode.removeChild(appendedHint);
      } else {
        this.input.removeAttribute('aria-describedby');
      }
      this.inputHintRead = true;
    }
  }

  /**
   * Handles keydown events on the item list.
   *
   * @param {Event} e
   *   The keydown event.
   */
  listKeyDown(e) {
    if (
      !this.ul.contains(document.activeElement) ||
      e.ctrlKey ||
      e.altKey ||
      e.metaKey ||
      e.keyCode === this.keyCode.TAB
    ) {
      return;
    }

    this.ul.querySelectorAll('[aria-selected="true"]').forEach((li) => {
      li.setAttribute('aria-selected', 'false');
    });

    switch (e.keyCode) {
      case this.keyCode.SPACE:
      case this.keyCode.RETURN:
        this.selectItem(document.activeElement, e);
        this.close();
        this.input.focus();
        break;

      case this.keyCode.ESC:
      case this.keyCode.TAB:
        this.input.focus();
        this.close();
        break;

      case this.keyCode.UP:
        this.focusPrev();
        break;

      case this.keyCode.DOWN:
        this.focusNext();
        break;

      default:
        break;
    }

    e.stopPropagation();
    e.preventDefault();
  }

  /**
   * Handles focus events on the item list.
   *
   * @param {Event} e
   *   The focus event.
   */
  // eslint-disable-next-line no-unused-vars, class-methods-use-this
  listFocus(e) {
    // Intentionally empty, can be overridden.
  }

  /**
   * Moves focus to the previous list item.
   */
  focusPrev() {
    this.preventCloseOnBlur = true;
    const currentItem = document.activeElement.getAttribute('data-autocomplete-item');
    const prevIndex = parseInt(currentItem, 10) - 1;
    const previousItem = this.ul.querySelector(`[data-autocomplete-item="${prevIndex}"]`);

    if (previousItem) {
      this.highlightItem(previousItem);
    } else {
      this.input.focus();
    }
  }

  /**
   * Moves focus to the next list item.
   */
  focusNext() {
    const currentItem = document.activeElement.getAttribute('data-autocomplete-item');
    const nextIndex = parseInt(currentItem, 10) + 1;
    const nextItem = this.ul.querySelector(`[data-autocomplete-item="${nextIndex}"]`);
    if (nextItem) {
      this.preventCloseOnBlur = true;
      this.highlightItem(nextItem);
    }
  }

  /**
   * Highlights and focuses a selected item.
   *
   * @param {HTMLElement} item
   *   The list item being selected.
   */
  highlightItem(item) {
    item.setAttribute('aria-selected', true);
    item.focus();
    const itemIndex = item.closest('[data-autocomplete-item]').getAttribute('data-autocomplete-item');

    /**
     * Fires when an item is highlighted.
     *
     * @event A11yAutocomplete#autocomplete-highlight
     * @property {Class} autocomplete - The autocomplete instance.
     * @property {Object} selected - the currently selected item,
     *   as an object with 'label' and 'value' properties.
     */
    this.triggerEvent('autocomplete-highlight', {
      selected: this.suggestions[itemIndex],
    });
    this.announceHighlight(item);
  }

  /**
   * Announces to assistive tech when an item is highlighted.
   *
   * @param {HTMLElement} item
   *   The list item being selected.
   */
  announceHighlight(item) {
    window.clearTimeout(this.announceTimeOutId);
    // Delay the announcement by 500 milliseconds. This prevents unnecessary
    // calls when a user is navigating quickly.
    this.announceTimeOutId = setTimeout(() => this.sendToLiveRegion(this.highlightMessage(item)), 500);
  }

  /**
   * A message announced when an item is highlighted.
   *
   * @param {HTMLElement} item
   *  The list item being highlighted.
   * @return {string}
   *  The message conveying that the item is highlighted
   */
  highlightMessage(item) {
    const itemIndex = item.closest('[data-autocomplete-item]').getAttribute('data-autocomplete-item');
    const selectedItem = this.suggestions[itemIndex].value;
    return this.options.highlightedAssistiveHint
      .replace('@selectedItem', selectedItem)
      .replace('@position', item.getAttribute('aria-posinset'))
      .replace('@count', this.ul.children.length);
  }

  /**
   * Handles keydown events on the autocomplete input.
   *
   * @param {Event} e
   *   The keydown event.
   */
  inputKeyDown(e) {
    const { keyCode } = e;
    if (this.isOpened) {
      if (keyCode === this.keyCode.ESC) {
        this.close();
      }
      if (keyCode === this.keyCode.DOWN) {
        e.preventDefault();
        this.preventCloseOnBlur = true;
        this.highlightItem(this.ul.querySelector('li'));
      }
    }
    this.removeAssistiveHint();
  }

  /**
   * Handles click events on the item list.
   *
   * @param {Event} e
   *   The click event.
   */
  itemClick(e) {
    const li = e.target;

    if (li && e.button === 0) {
      this.selectItem(li, e);
    }
  }

  /**
   * Selects an item in the autocomplete list.
   *
   * @param {Element} elementWithItem
   *  The element containing the item
   * @param {Event} e
   *  The event that triggered te selection.
   */
  selectItem(elementWithItem, e) {
    const itemIndex = elementWithItem.closest('[data-autocomplete-item]').getAttribute('data-autocomplete-item');
    const toSelect = this.suggestions[itemIndex];

    /**
     * Fires when an item is selected for addition. This event can be canceled
     * and prevent the addition of the item.
     *
     * @event A11yAutocomplete#autocomplete-select
     * @property {Class} autocomplete - The autocomplete instance.
     * @property {Object} toSelect - The item selected, as an object with 'label'
     *  and 'value' properties.
     */
    let selected = this.triggerEvent(
      'autocomplete-select',
      {
        selected: toSelect,
      },
      true,
      e,
    );
    if (selected) {
      this.replaceInputValue(elementWithItem);
      e.preventDefault();
      this.close();
      /**
       * Fires after an item is added to the autocomplete
       *
       * @event A11yAutocomplete#autocomplete-selection-added
       * @property {Class} autocomplete - The autocomplete instance.
       * @property {string} added - the text added to the input value.
       */
      this.triggerEvent('autocomplete-selection-added', {
        added: elementWithItem.textContent,
      });
    }
  }

  /**
   * Replaces the value of an input field when a new value is chosen.
   *
   * @param {Element} element
   *   The element with the item to be added.
   */
  replaceInputValue(element) {
    const itemIndex = element.closest('[data-autocomplete-item]').getAttribute('data-autocomplete-item');
    this.selected = this.suggestions[itemIndex];
    const separator = this.separator();
    if (separator.length > 0) {
      const before = this.previousItems(separator);
      this.input.value = `${before}${this.selected.value}`;
    } else {
      this.input.value = this.selected.value;
    }
  }

  /**
   * Returns the separator character.
   *
   * @return {string}
   *   The separator character or a zero-length string.
   *   If the autocomplete input does not support multiple items or has reached
   *   The maximum number of items that can be added, a zero-length string is
   *   returned as no separator is needed.
   */
  separator() {
    const { cardinality } = this.options;
    const numItems = this.splitValues().length - 1;
    return numItems < parseInt(cardinality, 10) || parseInt(cardinality, 10) <= 0 ? this.options.separatorChar : '';
  }

  /**
   * Gets all existing items in the autocomplete input.
   *
   * @param {string} separator
   *   The character separating the items.
   *
   * @return {string|string}
   *   The string of existing values in the input.
   */
  previousItems(separator) {
    const escapedSeparator = separator.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    const regex = new RegExp(`^.+${escapedSeparator}\\s*|`);
    const match = this.inputValue().match(regex)[0];
    return match && match.length > 0 ? `${match.trim()} ` : '';
  }

  /**
   * Triggers an autocomplete search.
   *
   * @param {Event} e
   *   The event triggering the search.
   */
  doSearch(e) {
    if (this.options.disabled) {
      return;
    }
    const inputId = this.input.getAttribute('id');
    const searchTerm = this.extractLastInputValue();
    if (searchTerm && searchTerm.length < this.options.minChars) {
      return;
    }

    /**
     * Fires just before a search. Can be used to cancel the search.
     *
     * @event A11yAutocomplete#autocomplete-pre-search
     * @property {Class} autocomplete - The autocomplete instance.
     */
    if (!this.triggerEvent('autocomplete-pre-search', {}, true, e)) {
      return;
    }
    if (!(inputId in this.cache)) {
      this.cache[inputId] = {};
    }

    if (searchTerm && searchTerm.length > 0) {
      if (Object.prototype.hasOwnProperty.call(this.cache[inputId], searchTerm)) {
        this.suggestionItems = this.cache[inputId][searchTerm];
        this.displayResults();
      } else if (this.options.list.length === 0 && this.options.path.length) {
        this.options.loadingClass.split(' ').forEach((className) => this.input.classList.add(className));
        fetch(this.queryUrl(searchTerm))
          .then((response) => response.json())
          .then((results) => {
            this.options.loadingClass.split(' ').forEach((className) => this.input.classList.remove(className));
            this.suggestionItems = results;

            this.displayResults();
            this.cache[inputId][searchTerm] = results;
          });
      } else {
        // If a predefined list was provided as an option, make this the
        // suggestion items.
        this.suggestionItems = this.options.list;
        this.displayResults();
      }
    } else {
      // If the search query is empty, provide an empty list of suggestions.
      this.suggestionItems = [];
      this.displayResults();
    }
  }

  /**
   * Takes input events and has them trigger searches when appropriate.
   *
   * @param {Event} e
   *   The input event.
   */
  inputListener(e) {
    if (!this.searchTimeOutId || this.options.searchDelay === 0) {
      this.searchTimeOutId = setTimeout(() => {
        this.doSearch(e);
        this.searchTimeOutId = null;
      }, this.options.searchDelay);
    }
  }

  /**
   * The URL used to search for a term.
   *
   * @param {string} searchTerm
   *   The term being searched for.
   *
   * @return {string}
   *   The URL to retrieve search results from.
   */
  queryUrl(searchTerm) {
    return `${this.options.path}?q=${searchTerm}`;
  }

  /**
   * Converts all suggestions into an object with value and label properties.
   */
  normalizeSuggestionItems() {
    this.suggestionItems = this.suggestionItems.map((item) => {
      if (typeof item === 'string') {
        item = { value: item, label: item };
      } else if (item.value && !item.label) {
        item = { value: item.value, label: item.value };
      } else if (item.label && !item.value) {
        item = { value: item.label, label: item.label };
      }

      return item;
    });
  }

  /**
   * Creates a suggestion list based on a typed value.
   *
   * @param {string} typed
   *   The typed value querying autocomplete.
   */
  prepareSuggestionList(typed) {
    this.normalizeSuggestionItems();
    if (typed) {
      this.suggestions = this.suggestionItems.filter((item) => this.filterResults(item, typed));
    } else {
      this.suggestions = this.suggestionItems;
    }
    if (this.options.sort !== false) {
      this.sortSuggestions();
    }
    this.totalSuggestions = this.suggestions.length;
    this.suggestions = this.suggestions.slice(0, parseInt(this.options.maxItems, 10));

    /**
     * Fires after suggestion items are retrieved, but before they are added to the DOM.
     *
     * @event A11yAutocomplete#autocomplete-response
     * @property {Class} autocomplete - The autocomplete instance.
     * @property {Object[]} list - an array of suggestions as objects with 'label'
     *  and 'value' properties.
     */
    this.triggerEvent('autocomplete-response', {
      list: this.suggestions,
    });

    this.suggestions.forEach((suggestion, index) => {
      this.ul.appendChild(this.suggestionItem(suggestion, index));
    });
  }

  /**
   * Displays the results retrieved in inputListener().
   */
  displayResults() {
    const typed = this.extractLastInputValue();
    this.ul.innerHTML = '';
    if (typed && this.suggestionItems.length > 0) {
      this.prepareSuggestionList(typed);
    }

    if (this.ul.children.length === 0) {
      this.close();
    } else {
      this.open();
    }

    window.clearTimeout(this.announceTimeOutId);
    // Delay the results announcement by 1400 milliseconds. This prevents
    // unnecessary calls when a user is typing quickly, and avoids the results
    // announcement being cut short by the screenreader stating the just-typed
    // character.
    this.announceTimeOutId = setTimeout(
      () => this.sendToLiveRegion(this.resultsMessage(this.ul.children.length)),
      1400,
    );
  }

  /**
   * Sorts the array of suggestions.
   */
  sortSuggestions() {
    this.suggestions.sort((prior, current) => (prior.label.toUpperCase() > current.label.toUpperCase() ? 1 : -1));
  }

  /**
   * Creates a list item that displays the suggestion.
   *
   * @param {object} suggestion
   *   A suggestion based on user input. It is an object with label and value
   *   properties.
   * @param {number} itemIndex
   *   The index of the item.
   *
   * @return {HTMLElement}
   *   A list item with the suggestion.
   */
  suggestionItem(suggestion, itemIndex) {
    const li = document.createElement('li');
    li.innerHTML = this.formatSuggestionItem(suggestion, li);
    if (this.options.itemClass.length > 0) {
      this.options.itemClass.split(' ').forEach((className) => li.classList.add(className));
    }
    li.setAttribute('role', 'option');
    li.setAttribute('tabindex', '-1');
    li.setAttribute('id', `suggestion-${this.count}-${itemIndex}`);
    li.setAttribute('data-autocomplete-item', itemIndex);
    li.setAttribute('aria-posinset', itemIndex + 1);
    li.setAttribute('aria-selected', 'false');
    li.onblur = (e) => this.blurHandler(e);

    return li;
  }

  /**
   * Formats how a suggestion is structured in the suggestion list.
   *
   * @param {object} suggestion
   *   Object with value and label properties.
   * @param {Element} li
   *   The list element.
   *
   * @return {string}
   *   The text and html of a suggestion item.
   */
  // eslint-disable-next-line no-unused-vars
  formatSuggestionItem(suggestion, li) {
    const propertyToDisplay = this.options.displayLabels ? 'label' : 'value';
    return suggestion[propertyToDisplay].trim();
  }

  /**
   * Opens the suggestion list.
   */
  open() {
    this.input.setAttribute('aria-expanded', 'true');
    this.ul.removeAttribute('hidden');
    this.ul.style.zIndex = this.options.listZindex;
    this.isOpened = true;
    this.ul.style.minWidth = `${this.input.offsetWidth - 4}px`;

    /**
     * Fires after the suggestion list opens.
     *
     * @event A11yAutocomplete#autocomplete-open
     * @property {Class} autocomplete - The autocomplete instance.
     */
    this.triggerEvent('autocomplete-open');
    if (this.options.autoFocus) {
      this.preventCloseOnBlur = true;
      this.highlightItem(this.ul.querySelector('[data-autocomplete-item="0"]'));
    }
  }

  /**
   * Closes the suggestion list.
   */
  close() {
    if (this.isOpened) {
      this.input.setAttribute('aria-expanded', 'false');
      this.ul.setAttribute('hidden', '');
      this.isOpened = false;

      /**
       * Fires after the suggestion list closes.
       *
       * @event A11yAutocomplete#autocomplete-close
       * @property {Class} autocomplete - The autocomplete instance.
       */
      this.triggerEvent('autocomplete-close');
    }
  }

  /**
   * Returns the last value of an multi-value textfield.
   *
   * @return {string}
   *   The last value of the input field.
   */
  extractLastInputValue() {
    return this.splitValues().pop();
  }

  /**
   * Gets the input value.
   *
   * @return {String}
   *   The input value.
   */
  inputValue() {
    return this.input.value;
  }

  /**
   * Helper splitting selections from the autocomplete value.
   *
   * @return {Array}
   *   Array of values, split by comma.
   */
  splitValues() {
    const value = this.inputValue();
    const result = [];
    let quote = false;
    let current = '';
    const valueLength = value.length;
    for (let i = 0; i < valueLength; i++) {
      const character = value.charAt(i);
      if (character === '"') {
        current += character;
        quote = !quote;
      } else if (character === this.options.separatorChar && !quote) {
        result.push(current.trim());
        current = '';
      } else {
        current += character;
      }
    }
    if (value.length > 0) {
      result.push(current.trim());
    }
    return result;
  }

  /**
   * Determines if a suggestion should be an available option.
   *
   * @param {object} suggestion
   *   A suggestion based on user input. It is an object with label and value
   *   properties.
   * @param {string} typed
   *   The text entered in the input field.
   *
   * @return {boolean}
   *   If the suggestion should be displayed in the results.
   */
  filterResults(suggestion, typed) {
    const { firstCharacterDenylist, cardinality } = this.options;
    const suggestionValue = suggestion.value;
    const currentValues = this.splitValues();

    // Prevent suggestions if the first input character is in the denylist, if
    // the suggestion has already been added to the field, or if the maximum
    // number of items have been reached.
    if (
      firstCharacterDenylist.indexOf(typed[0]) !== -1 ||
      (cardinality > 0 && currentValues.length > cardinality) ||
      (currentValues.indexOf(suggestionValue) !== -1 && !this.options.allowRepeatValues)
    ) {
      return false;
    }

    return RegExp(
      this.extractLastInputValue()
        .trim()
        .replace(/[-\\^$*+?.()|[\]{}]/g, '\\$&'),
      'i',
    ).test(suggestionValue);
  }

  /**
   * Sends a message to the configured live region.
   *
   * @param {string} message
   *   The message to be sent to the live region.
   */
  sendToLiveRegion(message) {
    if (this.liveRegion) {
      this.liveRegion.textContent = message;
    }
  }

  /**
   * A message regarding the number of suggestions found.
   *
   * @param {number} count
   *   The number of suggestions found.
   *
   * @return {string}
   *   A message based on the number of suggestions found.
   */
  resultsMessage(count) {
    const { maxItems } = this.options;
    let message = '';
    if (count === 0) {
      message = this.options.noResultsAssistiveHint;
    } else if (parseInt(maxItems, 10) === this.totalSuggestions) {
      message = this.options.moreThanMaxResultsAssistiveHint;
    } else if (count === 1) {
      message = this.options.oneResultAssistiveHint;
    } else {
      message = this.options.someResultsAssistiveHint;
    }

    return message.replace('@count', count);
  }

  /**
   * A message stating the number of characters needed to trigger autocomplete.
   *
   * @return {string}
   *  The minimum characters message.
   */
  minCharsMessage() {
    if (this.options.minChars > 1) {
      return `${this.options.minCharAssistiveHint.replace('@count', this.options.minChars)}. `;
    }
    return '';
  }

  /**
   * Remove all event listeners added by this class.
   */
  destroy() {
    Object.keys(this.events).forEach((elementName) => {
      Object.keys(this.events[elementName]).forEach((eventName) => {
        this[elementName].removeEventListener(eventName, this.events[elementName][eventName]);
      });
    });
    this.ul.remove();

    /**
     * Fires after the instance is destroyed.
     *
     * @event A11yAutocomplete#autocomplete-destroy
     * @property {Class} autocomplete - What remains of the autocomplete instance.
     */
    this.triggerEvent('autocomplete-destroy');
  }

  /**
   * Dispatches an autocomplete event
   *
   * @param {string} type
   *   The event type.
   * @param {object} additionalData
   *   Additional data attached to the event's `details` property.
   * @param {boolean} cancelable
   *   If the dispatched event should be cancelable.
   * @param {Event} originalEvent
   *   A native event that called the function that triggers a custom event.
   *
   * @return {boolean}
   *   If the event triggered successfully.
   */
  triggerEvent(type, additionalData = {}, cancelable = false, originalEvent) {
    const event = new CustomEvent(type, {
      detail: {
        autocomplete: this,
        ...additionalData,
      },
      cancelable,
      originalEvent,
    });
    if (originalEvent) {
      event.originalEvent = originalEvent;
    }

    return this.input.dispatchEvent(event);
  }
}

window.A11yAutocomplete = A11yAutocomplete;
