/**
 * @file
 * Overrides jQuery event functions.
 */

(($, Drupal) => {
  const oldOn = $.fn.on;

  /**
   * Returns whichever of the args is a function.
   *
   * jQuery on() accepts multiple argument signatures, and this is used to
   * determine which of those is the handler.
   *
   * @param {*} fn
   *   Might be a function.
   * @param {*} data
   *   Might be a function.
   * @param {*} selector
   *   Might be a function.
   * @return {null|function}
   *   The first function found or null.
   */
  const findHandler = (fn, data, selector) => {
    if (typeof fn === 'function') {
      return fn;
    }
    if (typeof data === 'function') {
      return data;
    }
    if (typeof selector === 'function') {
      return selector;
    }
    return null;
  };

  $.fn.extend({
    on(...args) {
      const [types, selector, data, fn, one] = args;

      // For shimming autocomplete
      const eventsToAddListenersTo = {};
      const autocompleteEvents = {
        autocompletechange: 'autocomplete-change',
        autocompleteclose: 'autocomplete-close',
        autocompletecreate: 'autocomplete-created',
        autocompletefocus: 'autocomplete-highlight',
        autocompleteopen: 'autocomplete-open',
        autocompleteresponse: 'autocomplete-response',
        autocompletesearch: 'autocomplete-pre-search',
        autocompleteselect: 'autocomplete-select',
      };
      const autocompleteKeys = Object.keys(autocompleteEvents);

      // Find any autocomplete events and add them to an object with the event
      // name as the key and the handler as the value.
      if (typeof types === 'string' && types.indexOf('autocomplete') !== -1) {
        // The handler could be one of several arguments.
        const handler = findHandler(fn, data, selector);
        types.split(' ').forEach((eventName) => {
          if (autocompleteKeys.includes(eventName.split('.')[0])) {
            eventsToAddListenersTo[eventName] = handler;
          }
        });
      } else if (typeof types === 'object') {
        Object.keys(types).forEach((eventName) => {
          if (autocompleteKeys.includes(eventName)) {
            eventsToAddListenersTo[eventName] = types[eventName];
          }
        });
      }

      const autocompleteEventsToShim = Object.keys(eventsToAddListenersTo);

      // If there are any jQuery UI autocomplete events, they must be shimmed to
      // Drupal autocomplete events.
      if (autocompleteEventsToShim.length) {
        const id = this.attr('id');
        const instance = Drupal.Autocomplete.instances[id];
        const that = this;
        const config = {};
        if (one === 1) {
          config.once = true;
        }
        autocompleteEventsToShim.forEach((eventName) => {
          const eventHandler = eventsToAddListenersTo[eventName];
          // eslint-disable-next-line func-names
          const shimmedEventHandler = function (e) {
            // @todo, how much of originalEvent needs to be backwards compatible?
            const ui = {};
            if (eventName === 'autocompleteresponse') {
              ui.content = e.detail.list;
            }
            if (eventName === 'autocompletechange') {
              e.originalEvent = $.Event('blur');
              ui.item = instance.selected;
            }
            if (eventName === 'autocompletefocus') {
              ui.item = e.detail.selected;
              e.originalEvent = $.Event('menufocus');
            }
            if (eventName === 'autocompleteselect') {
              ui.item = e.detail.selected;
              e.originalEvent.type = 'menuselect';
            }
            if (eventName === 'autocompleteclose') {
              e.originalEvent = $.Event('menuselect');
            }
            e.type = eventName;

            const handle = eventHandler.bind(that);
            const eventReturn = handle(
              { ...$.Event(eventName, e), type: eventName },
              ui,
            );

            if (eventReturn === false) {
              e.preventDefault();
            }
            return eventReturn;
          };

          instance.input.addEventListener(
            autocompleteEvents[eventName],
            shimmedEventHandler,
            config,
          );
        });
      }
      // End of logic for shimming autocomplete.

      // Run jQuery's default on().
      return oldOn.apply(this, args);
    },
  });
})(jQuery, Drupal);
