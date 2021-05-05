/**
 * @file
 * Overrides jQuery event functions.
 */

(($, Drupal) => {
  // The jQuery `on()` function will be overridden below, make a copy of the
  // original so it's available to use cases that don't require any of the
  // additional logic in the override.
  const oldOn = $.fn.on;

  /**
   * Returns whichever of the args is a function.
   *
   * jQuery on() accepts multiple argument signatures, and this is used to
   * determine which of those is the handler.
   *
   * @param {*} arg1
   *   Might be a function.
   * @param {*} arg2
   *   Might be a function.
   * @param {*} arg3
   *   Might be a function.
   * @return {null|function}
   *   The first function found or null.
   */
  const findHandler = (arg1, arg2, arg3) => {
    if (typeof arg1 === 'function') {
      return arg1;
    }
    if (typeof arg2 === 'function') {
      return arg2;
    }
    if (typeof arg3 === 'function') {
      return arg3;
    }
    return null;
  };

  /**
   * Autocomplete-specific logic used by the jQuery `on()` override.
   *
   * This is performed in a separate function so the `on()` override can better
   * accommodate additional customizations that may not be autocomplete
   * specific.
   *
   * The first 5 parameters match those sent to `on()`. The sixth is the jQuery
   * object calling `on()`.
   *
   * @param {*} types
   *   The first argument received by jQuery `on()`.
   * @param {*} selector
   *   The second argument received by jQuery `on()`.
   * @param {*} data
   *   The third argument received by jQuery `on()`.
   * @param {*} fn
   *   The fourth argument received by jQuery `on()`.
   * @param {*} one
   *   The fifth argument received by jQuery `on()`.
   * @param {jQuery} that
   *   The jQuery object that called `on()`.
   *
   * @see https://api.jquery.com/on/ for details on parameters 1-5.
   */
  // eslint-disable-next-line func-names
  const processAutocompleteEvents = function (
    types,
    selector,
    data,
    fn,
    one,
    that,
  ) {
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
        if (autocompleteKeys.includes(eventName.split('.')[0]) && handler) {
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
      const id = that.attr('id');
      const instance = Drupal.Autocomplete.instances[id];
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
            e.originalEvent = $.Event('menuselect');
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
  };

  $.fn.extend({
    on(...args) {
      const [types, selector, data, fn, one] = args;

      // Autocomplete events must receive additional processing as jQuery UI
      // autocomplete is no longer part of Drupal core.
      processAutocompleteEvents(types, selector, data, fn, one, this);

      // Run jQuery's default on().
      return oldOn.apply(this, args);
    },
  });
})(jQuery, Drupal);
