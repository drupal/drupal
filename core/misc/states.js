/**
 * Drupal's states library.
 */
(function ($) {

  "use strict";

  /**
   * The base States namespace.
   *
   * Having the local states variable allows us to use the States namespace
   * without having to always declare "Drupal.states".
   */
  var states = Drupal.states = {
    // An array of functions that should be postponed.
    postponed: []
  };

  /**
   * Attaches the states.
   */
  Drupal.behaviors.states = {
    attach: function (context, settings) {
      var $states = $(context).find('[data-drupal-states]');
      var config, state;
      for (var i = 0, il = $states.length; i < il; i += 1) {
        config = JSON.parse($states[i].getAttribute('data-drupal-states'));
        for (state in config) {
          if (config.hasOwnProperty(state)) {
            new states.Dependent({
              element: $($states[i]),
              state: states.State.sanitize(state),
              constraints: config[state]
            });
          }
        }
      }

      // Execute all postponed functions now.
      while (states.postponed.length) {
        (states.postponed.shift())();
      }
    }
  };

  /**
   * Object representing an element that depends on other elements.
   *
   * @param args
   *   Object with the following keys (all of which are required):
   *   - element: A jQuery object of the dependent element
   *   - state: A State object describing the state that is dependent
   *   - constraints: An object with dependency specifications. Lists all elements
   *     that this element depends on. It can be nested and can contain arbitrary
   *     AND and OR clauses.
   */
  states.Dependent = function (args) {
    $.extend(this, { values: {}, oldValue: null }, args);

    this.dependees = this.getDependees();
    for (var selector in this.dependees) {
      if (this.dependees.hasOwnProperty(selector)) {
        this.initializeDependee(selector, this.dependees[selector]);
      }
    }
  };

  /**
   * Comparison functions for comparing the value of an element with the
   * specification from the dependency settings. If the object type can't be
   * found in this list, the === operator is used by default.
   */
  states.Dependent.comparisons = {
    'RegExp': function (reference, value) {
      return reference.test(value);
    },
    'Function': function (reference, value) {
      // The "reference" variable is a comparison function.
      return reference(value);
    },
    'Number': function (reference, value) {
      // If "reference" is a number and "value" is a string, then cast reference
      // as a string before applying the strict comparison in compare(). Otherwise
      // numeric keys in the form's #states array fail to match string values
      // returned from jQuery's val().
      return (typeof value === 'string') ? compare(reference.toString(), value) : compare(reference, value);
    }
  };

  states.Dependent.prototype = {
    /**
     * Initializes one of the elements this dependent depends on.
     *
     * @param selector
     *   The CSS selector describing the dependee.
     * @param dependeeStates
     *   The list of states that have to be monitored for tracking the
     *   dependee's compliance status.
     */
    initializeDependee: function (selector, dependeeStates) {
      var state, self = this;

      function stateEventHandler(e) {
        self.update(e.data.selector, e.data.state, e.value);
      }

      // Cache for the states of this dependee.
      this.values[selector] = {};

      for (var i in dependeeStates) {
        if (dependeeStates.hasOwnProperty(i)) {
          state = dependeeStates[i];
          // Make sure we're not initializing this selector/state combination twice.
          if ($.inArray(state, dependeeStates) === -1) {
            continue;
          }

          state = states.State.sanitize(state);

          // Initialize the value of this state.
          this.values[selector][state.name] = null;

          // Monitor state changes of the specified state for this dependee.
          $(selector).on('state:' + state, {selector: selector, state: state}, stateEventHandler);

          // Make sure the event we just bound ourselves to is actually fired.
          new states.Trigger({ selector: selector, state: state });
        }
      }
    },

    /**
     * Compares a value with a reference value.
     *
     * @param reference
     *   The value used for reference.
     * @param selector
     *   CSS selector describing the dependee.
     * @param state
     *   A State object describing the dependee's updated state.
     *
     * @return
     *   true or false.
     */
    compare: function (reference, selector, state) {
      var value = this.values[selector][state.name];
      if (reference.constructor.name in states.Dependent.comparisons) {
        // Use a custom compare function for certain reference value types.
        return states.Dependent.comparisons[reference.constructor.name](reference, value);
      }
      else {
        // Do a plain comparison otherwise.
        return compare(reference, value);
      }
    },

    /**
     * Update the value of a dependee's state.
     *
     * @param selector
     *   CSS selector describing the dependee.
     * @param state
     *   A State object describing the dependee's updated state.
     * @param value
     *   The new value for the dependee's updated state.
     */
    update: function (selector, state, value) {
      // Only act when the 'new' value is actually new.
      if (value !== this.values[selector][state.name]) {
        this.values[selector][state.name] = value;
        this.reevaluate();
      }
    },

    /**
     * Triggers change events in case a state changed.
     */
    reevaluate: function () {
      // Check whether any constraint for this dependent state is satisifed.
      var value = this.verifyConstraints(this.constraints);

      // Only invoke a state change event when the value actually changed.
      if (value !== this.oldValue) {
        // Store the new value so that we can compare later whether the value
        // actually changed.
        this.oldValue = value;

        // Normalize the value to match the normalized state name.
        value = invert(value, this.state.invert);

        // By adding "trigger: true", we ensure that state changes don't go into
        // infinite loops.
        this.element.trigger({ type: 'state:' + this.state, value: value, trigger: true });
      }
    },

    /**
     * Evaluates child constraints to determine if a constraint is satisfied.
     *
     * @param constraints
     *   A constraint object or an array of constraints.
     * @param selector
     *   The selector for these constraints. If undefined, there isn't yet a
     *   selector that these constraints apply to. In that case, the keys of the
     *   object are interpreted as the selector if encountered.
     *
     * @return
     *   true or false, depending on whether these constraints are satisfied.
     */
    verifyConstraints: function (constraints, selector) {
      var result;
      if ($.isArray(constraints)) {
        // This constraint is an array (OR or XOR).
        var hasXor = $.inArray('xor', constraints) === -1;
        for (var i = 0, len = constraints.length; i < len; i++) {
          if (constraints[i] !== 'xor') {
            var constraint = this.checkConstraints(constraints[i], selector, i);
            // Return if this is OR and we have a satisfied constraint or if this
            // is XOR and we have a second satisfied constraint.
            if (constraint && (hasXor || result)) {
              return hasXor;
            }
            result = result || constraint;
          }
        }
      }
      // Make sure we don't try to iterate over things other than objects. This
      // shouldn't normally occur, but in case the condition definition is bogus,
      // we don't want to end up with an infinite loop.
      else if ($.isPlainObject(constraints)) {
        // This constraint is an object (AND).
        for (var n in constraints) {
          if (constraints.hasOwnProperty(n)) {
            result = ternary(result, this.checkConstraints(constraints[n], selector, n));
            // False and anything else will evaluate to false, so return when any
            // false condition is found.
            if (result === false) { return false; }
          }
        }
      }
      return result;
    },

    /**
     * Checks whether the value matches the requirements for this constraint.
     *
     * @param value
     *   Either the value of a state or an array/object of constraints. In the
     *   latter case, resolving the constraint continues.
     * @param selector
     *   The selector for this constraint. If undefined, there isn't yet a
     *   selector that this constraint applies to. In that case, the state key is
     *   propagates to a selector and resolving continues.
     * @param state
     *   The state to check for this constraint. If undefined, resolving
     *   continues.
     *   If both selector and state aren't undefined and valid non-numeric
     *   strings, a lookup for the actual value of that selector's state is
     *   performed. This parameter is not a State object but a pristine state
     *   string.
     *
     * @return
     *   true or false, depending on whether this constraint is satisfied.
     */
    checkConstraints: function (value, selector, state) {
      // Normalize the last parameter. If it's non-numeric, we treat it either as
      // a selector (in case there isn't one yet) or as a trigger/state.
      if (typeof state !== 'string' || (/[0-9]/).test(state[0])) {
        state = null;
      }
      else if (typeof selector === 'undefined') {
        // Propagate the state to the selector when there isn't one yet.
        selector = state;
        state = null;
      }

      if (state !== null) {
        // constraints is the actual constraints of an element to check for.
        state = states.State.sanitize(state);
        return invert(this.compare(value, selector, state), state.invert);
      }
      else {
        // Resolve this constraint as an AND/OR operator.
        return this.verifyConstraints(value, selector);
      }
    },

    /**
     * Gathers information about all required triggers.
     */
    getDependees: function () {
      var cache = {};
      // Swivel the lookup function so that we can record all available selector-
      // state combinations for initialization.
      var _compare = this.compare;
      this.compare = function (reference, selector, state) {
        (cache[selector] || (cache[selector] = [])).push(state.name);
        // Return nothing (=== undefined) so that the constraint loops are not
        // broken.
      };

      // This call doesn't actually verify anything but uses the resolving
      // mechanism to go through the constraints array, trying to look up each
      // value. Since we swivelled the compare function, this comparison returns
      // undefined and lookup continues until the very end. Instead of lookup up
      // the value, we record that combination of selector and state so that we
      // can initialize all triggers.
      this.verifyConstraints(this.constraints);
      // Restore the original function.
      this.compare = _compare;

      return cache;
    }
  };

  states.Trigger = function (args) {
    $.extend(this, args);

    if (this.state in states.Trigger.states) {
      this.element = $(this.selector);

      // Only call the trigger initializer when it wasn't yet attached to this
      // element. Otherwise we'd end up with duplicate events.
      if (!this.element.data('trigger:' + this.state)) {
        this.initialize();
      }
    }
  };

  states.Trigger.prototype = {
    initialize: function () {
      var trigger = states.Trigger.states[this.state];

      if (typeof trigger === 'function') {
        // We have a custom trigger initialization function.
        trigger.call(window, this.element);
      }
      else {
        for (var event in trigger) {
          if (trigger.hasOwnProperty(event)) {
            this.defaultTrigger(event, trigger[event]);
          }
        }
      }

      // Mark this trigger as initialized for this element.
      this.element.data('trigger:' + this.state, true);
    },

    defaultTrigger: function (event, valueFn) {
      var oldValue = valueFn.call(this.element);

      // Attach the event callback.
      this.element.on(event, $.proxy(function (e) {
        var value = valueFn.call(this.element, e);
        // Only trigger the event if the value has actually changed.
        if (oldValue !== value) {
          this.element.trigger({ type: 'state:' + this.state, value: value, oldValue: oldValue });
          oldValue = value;
        }
      }, this));

      states.postponed.push($.proxy(function () {
        // Trigger the event once for initialization purposes.
        this.element.trigger({ type: 'state:' + this.state, value: oldValue, oldValue: null });
      }, this));
    }
  };

  /**
   * This list of states contains functions that are used to monitor the state
   * of an element. Whenever an element depends on the state of another element,
   * one of these trigger functions is added to the dependee so that the
   * dependent element can be updated.
   */
  states.Trigger.states = {
    // 'empty' describes the state to be monitored
    empty: {
      // 'keyup' is the (native DOM) event that we watch for.
      'keyup': function () {
        // The function associated to that trigger returns the new value for the
        // state.
        return this.val() === '';
      }
    },

    checked: {
      'change': function () {
        // prop() and attr() only takes the first element into account. To support
        // selectors matching multiple checkboxes, iterate over all and return
        // whether any is checked.
        var checked = false;
        this.each(function () {
          // Use prop() here as we want a boolean of the checkbox state.
          // @see http://api.jquery.com/prop/
          checked = $(this).prop('checked');
          // Break the each() loop if this is checked.
          return !checked;
        });
        return checked;
      }
    },

    // For radio buttons, only return the value if the radio button is selected.
    value: {
      'keyup': function () {
        // Radio buttons share the same :input[name="key"] selector.
        if (this.length > 1) {
          // Initial checked value of radios is undefined, so we return false.
          return this.filter(':checked').val() || false;
        }
        return this.val();
      },
      'change': function () {
        // Radio buttons share the same :input[name="key"] selector.
        if (this.length > 1) {
          // Initial checked value of radios is undefined, so we return false.
          return this.filter(':checked').val() || false;
        }
        return this.val();
      }
    },

    collapsed: {
      'collapsed': function (e) {
        return (typeof e !== 'undefined' && 'value' in e) ? e.value : !this.is('[open]');
      }
    }
  };


  /**
   * A state object is used for describing the state and performing aliasing.
   */
  states.State = function (state) {
    // We may need the original unresolved name later.
    this.pristine = this.name = state;

    // Normalize the state name.
    while (true) {
      // Iteratively remove exclamation marks and invert the value.
      while (this.name.charAt(0) === '!') {
        this.name = this.name.substring(1);
        this.invert = !this.invert;
      }

      // Replace the state with its normalized name.
      if (this.name in states.State.aliases) {
        this.name = states.State.aliases[this.name];
      }
      else {
        break;
      }
    }
  };

  /**
   * Creates a new State object by sanitizing the passed value.
   */
  states.State.sanitize = function (state) {
    if (state instanceof states.State) {
      return state;
    }
    else {
      return new states.State(state);
    }
  };

  /**
   * This list of aliases is used to normalize states and associates negated names
   * with their respective inverse state.
   */
  states.State.aliases = {
    'enabled': '!disabled',
    'invisible': '!visible',
    'invalid': '!valid',
    'untouched': '!touched',
    'optional': '!required',
    'filled': '!empty',
    'unchecked': '!checked',
    'irrelevant': '!relevant',
    'expanded': '!collapsed',
    'open': '!collapsed',
    'closed': 'collapsed',
    'readwrite': '!readonly'
  };

  states.State.prototype = {
    invert: false,

    /**
     * Ensures that just using the state object returns the name.
     */
    toString: function () {
      return this.name;
    }
  };

  /**
   * Global state change handlers. These are bound to "document" to cover all
   * elements whose state changes. Events sent to elements within the page
   * bubble up to these handlers. We use this system so that themes and modules
   * can override these state change handlers for particular parts of a page.
   */

  $(document).on('state:disabled', function (e) {
    // Only act when this change was triggered by a dependency and not by the
    // element monitoring itself.
    if (e.trigger) {
      $(e.target)
        .prop('disabled', e.value)
        .closest('.form-item, .form-submit, .form-wrapper').toggleClass('form-disabled', e.value)
        .find('select, input, textarea').prop('disabled', e.value);

      // Note: WebKit nightlies don't reflect that change correctly.
      // See https://bugs.webkit.org/show_bug.cgi?id=23789
    }
  });

  $(document).on('state:required', function (e) {
    if (e.trigger) {
      if (e.value) {
        var $label = $(e.target).attr({ 'required': 'required', 'aria-required': 'aria-required' }).closest('.form-item, .form-wrapper').find('label');
        // Avoids duplicate required markers on initialization.
        if (!$label.find('.form-required').length) {
          $label.append(Drupal.theme('requiredMarker'));
        }
      }
      else {
        $(e.target).removeAttr('required aria-required').closest('.form-item, .form-wrapper').find('label .form-required').remove();
      }
    }
  });

  $(document).on('state:visible', function (e) {
    if (e.trigger) {
      $(e.target).closest('.form-item, .form-submit, .form-wrapper').toggle(e.value);
    }
  });

  $(document).on('state:checked', function (e) {
    if (e.trigger) {
      $(e.target).prop('checked', e.value);
    }
  });

  $(document).on('state:collapsed', function (e) {
    if (e.trigger) {
      if ($(e.target).is('[open]') === e.value) {
        $(e.target).find('> summary a').trigger('click');
      }
    }
  });


  /**
   * These are helper functions implementing addition "operators" and don't
   * implement any logic that is particular to states.
   */

  // Bitwise AND with a third undefined state.
  function ternary(a, b) {
    return typeof a === 'undefined' ? b : (typeof b === 'undefined' ? a : a && b);
  }

  // Inverts a (if it's not undefined) when invertState is true.
  function invert(a, invertState) {
    return (invertState && typeof a !== 'undefined') ? !a : a;
  }

  // Compares two values while ignoring undefined values.
  function compare(a, b) {
    return (a === b) ? (typeof a === 'undefined' ? a : true) : (typeof a === 'undefined' || typeof b === 'undefined');
  }

  $.extend(Drupal.theme, {
    requiredMarker: function () {
      return '<abbr class="form-required" title="' + Drupal.t('This field is required.') + '">*</abbr>';
    }
  });

})(jQuery);
