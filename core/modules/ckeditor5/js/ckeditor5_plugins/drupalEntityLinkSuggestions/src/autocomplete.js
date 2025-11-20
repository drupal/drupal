const $ = jQuery;

/**
 * Override jQuery UI _renderItem function to output HTML by default.
 *
 * This uses function() syntax as required by jQuery UI.
 *
 * @param {object} ul
 *   The <ul> element that the newly created <li> element must be appended to.
 * @param {object} item
 *  The list item to append.
 *
 * @return {object}
 *   jQuery collection of the ul element.
 */
function renderItem(ul, item) {
  const $line = $('<li class="entity-link-suggestions-result-line">');
  const $wrapper = $(
    '<div class="entity-link-suggestions-result-line-wrapper">',
  );
  $wrapper.append(
    `<span class="entity-link-suggestions-result-line--title">${item.label}</span>`,
  );
  if (item.hasOwnProperty('description')) {
    $wrapper.append(
      `<span class="entity-link-suggestions-result-line--description">${item.description}</span>`,
    );
  }
  return $line.append($wrapper).appendTo(ul);
}

/**
 * Override jQuery UI _renderMenu function to handle groups.
 *
 * This uses function() syntax as required by jQuery UI.
 *
 * @param {object} ul
 *   An empty <ul> element to use as the widget's menu.
 * @param {array} items
 *   An Array of items that match the user typed term.
 */
function renderMenu(ul, items) {
  const groupedItems = {};
  items.forEach((item) => {
    const group = item.hasOwnProperty('group') ? item.group : '';
    if (!groupedItems.hasOwnProperty(group)) {
      groupedItems[group] = [];
    }
    groupedItems[group].push(item);
  });

  Object.keys(groupedItems).forEach((groupLabel) => {
    const groupItems = groupedItems[groupLabel];
    if (groupLabel.length) {
      ul.append(
        `<li class="entity-link-suggestions-result-line--group ui-menu-divider">${groupLabel}</li>`,
      );
    }
    groupItems.forEach((item) => {
      this.element.autocomplete('instance')._renderItemData(ul, item);
    });
  });
}

export default function initializeAutocomplete(element, settings) {
  const {
    autocompleteUrl,
    selectHandler,
    closeHandler,
    openHandler,
    queryParams,
  } = settings;
  const autocomplete = {
    cache: {},
    ajax: {
      dataType: 'json',
      jsonp: false,
    },
  };

  /**
   * JQuery UI autocomplete source callback.
   *
   * @param {object} request
   *   The request object.
   * @param {function} response
   *   The function to call with the response.
   */
  function sourceData(request, response) {
    const { cache } = autocomplete;
    const { term } = request;

    /**
     * Transforms the data object into an array and update autocomplete results.
     *
     * @param {object} data
     *   The data sent back from the server.
     */
    function sourceCallbackHandler(data) {
      cache[term] = data.suggestions;
      response(data.suggestions);
    }

    // Get the desired term and construct the autocomplete URL for it.

    // Check if the term is already cached.
    if (cache.hasOwnProperty(term)) {
      response(cache[term]);
    } else {
      const data = queryParams;
      data.q = term;
      $.ajax(autocompleteUrl, {
        success: sourceCallbackHandler,
        data,
        ...autocomplete.ajax,
      });
    }
  }

  const options = {
    appendTo: element.closest('.ck-labeled-field-view'),
    source: sourceData,
    select: selectHandler,
    focus: () => false,
    search: () => !options.isComposing,
    close: closeHandler,
    open: openHandler,
    minLength: 1,
    isComposing: false,
  };
  const $auto = $(element).autocomplete(options);

  // Override a few things.
  const instance = $auto.data('ui-autocomplete');
  instance
    .widget()
    .menu(
      'option',
      'items',
      '> :not(.entity-link-suggestions-result-line--group)',
    );
  instance._renderMenu = renderMenu;
  instance._renderItem = renderItem;

  $auto
    .autocomplete('widget')
    .addClass('ck-reset_all-excluded entity-link-suggestions-ui-autocomplete');

  $auto.on('click', () => {
    $auto.autocomplete('search', $auto[0].value);
  });

  // Use CompositionEvent to handle IME inputs. It requests remote server on "compositionend" event only.
  $auto.on('compositionstart.autocomplete', () => {
    options.isComposing = true;
  });
  $auto.on('compositionend.autocomplete', () => {
    options.isComposing = false;
  });

  return $auto;
}
