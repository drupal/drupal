/**
 * @file
 * Manages page tabbing modifications made by modules.
 */

(function ($, Drupal) {

  "use strict";

  /**
   * Provides an API for managing page tabbing order modifications.
   */
  function TabbingManager() {
    // Tabbing sets are stored as a stack. The active set is at the top of the
    // stack. We use a JavaScript array as if it were a stack; we consider the
    // first element to be the bottom and the last element to be the top. This
    // allows us to use JavaScript's built-in Array.push() and Array.pop()
    // methods.
    this.stack = [];
  }

  /**
   * Add public methods to the TabbingManager class.
   */
  $.extend(TabbingManager.prototype, {
    /**
     * Constrain tabbing to the specified set of elements only.
     *
     * Makes elements outside of the specified set of elements unreachable via the
     * tab key.
     *
     * @param jQuery elements
     *   The set of elements to which tabbing should be constrained. Can also be
     *   a jQuery-compatible selector string.
     *
     * @return TabbingContext
     */
    constrain: function (elements) {
      // Deactivate all tabbingContexts to prepare for the new constraint. A
      // tabbingContext instance will only be reactivated if the stack is unwound
      // to it in the _unwindStack() method.
      for (var i = 0, il = this.stack.length; i < il; i++) {
        this.stack[i].deactivate();
      }

      // The "active tabbing set" are the elements tabbing should be constrained
      // to.
      var $elements = $(elements).find(':tabbable').addBack(':tabbable');

      var tabbingContext = new TabbingContext({
        // The level is the current height of the stack before this new
        // tabbingContext is pushed on top of the stack.
        level: this.stack.length,
        $tabbableElements: $elements
      });

      this.stack.push(tabbingContext);

      // Activates the tabbingContext; this will manipulate the DOM to constrain
      // tabbing.
      tabbingContext.activate();

      // Allow modules to respond to the constrain event.
      $(document).trigger('drupalTabbingConstrained', tabbingContext);

      return tabbingContext;
    },

    /**
     * Restores a former tabbingContext when an active tabbingContext is released.
     *
     * The TabbingManager stack of tabbingContext instances will be unwound from
     * the top-most released tabbingContext down to the first non-released
     * tabbingContext instance. This non-released instance is then activated.
     */
    release: function () {
      // Unwind as far as possible: find the topmost non-released tabbingContext.
      var toActivate = this.stack.length - 1;
      while (toActivate >= 0 && this.stack[toActivate].released) {
        toActivate--;
      }

      // Delete all tabbingContexts after the to be activated one. They have
      // already been deactivated, so their effect on the DOM has been reversed.
      this.stack.splice(toActivate + 1);

      // Get topmost tabbingContext, if one exists, and activate it.
      if (toActivate >= 0) {
        this.stack[toActivate].activate();
      }
    },

    /**
     * Makes all elements outside the of the tabbingContext's set untabbable.
     *
     * Elements made untabble have their original tabindex and autfocus values
     * stored so that they might be restored later when this tabbingContext
     * is deactivated.
     *
     * @param TabbingContext tabbingContext
     *   The TabbingContext instance that has been activated.
     */
    activate: function (tabbingContext) {
      var $set = tabbingContext.$tabbableElements;
      var level = tabbingContext.level;
      // Determine which elements are reachable via tabbing by default.
      var $disabledSet = $(':tabbable')
        // Exclude elements of the active tabbing set.
        .not($set);
      // Set the disabled set on the tabbingContext.
      tabbingContext.$disabledElements = $disabledSet;
      // Record the tabindex for each element, so we can restore it later.
      for (var i = 0, il = $disabledSet.length; i < il; i++) {
        this.recordTabindex($disabledSet.eq(i), level);
      }
      // Make all tabbable elements outside of the active tabbing set unreachable.
      $disabledSet
        .prop('tabindex', -1)
        .prop('autofocus', false);

      // Set focus on an element in the tabbingContext's set of tabbable elements.
      // First, check if there is an element with an autofocus attribute. Select
      // the last one from the DOM order.
      var $hasFocus = $set.filter('[autofocus]').eq(-1);
      // If no element in the tabbable set has an autofocus attribute, select the
      // first element in the set.
      if ($hasFocus.length === 0) {
        $hasFocus = $set.eq(0);
      }
      $hasFocus.trigger('focus');
    },

    /**
     * Restores that tabbable state of a tabbingContext's disabled elements.
     *
     * Elements that were made untabble have their original tabindex and autfocus
     * values restored.
     *
     * @param TabbingContext tabbingContext
     *   The TabbingContext instance that has been deactivated.
     */
    deactivate: function (tabbingContext) {
      var $set = tabbingContext.$disabledElements;
      var level = tabbingContext.level;
      for (var i = 0, il = $set.length; i < il; i++) {
        this.restoreTabindex($set.eq(i), level);
      }
    },

    /**
     * Records the tabindex and autofocus values of an untabbable element.
     *
     * @param jQuery $set
     *   The set of elements that have been disabled.
     * @param Number level
     *   The stack level for which the tabindex attribute should be recorded.
     */
    recordTabindex: function ($el, level) {
      var tabInfo = $el.data('drupalOriginalTabIndices') || {};
      tabInfo[level] = {
        tabindex: $el[0].getAttribute('tabindex'),
        autofocus: $el[0].hasAttribute('autofocus')
      };
      $el.data('drupalOriginalTabIndices', tabInfo);
    },

    /**
     * Restores the tabindex and autofocus values of a reactivated element.
     *
     * @param jQuery $el
     *   The element that is being reactivated.
     * @param Number level
     *   The stack level for which the tabindex attribute should be restored.
     */
    restoreTabindex: function ($el, level) {
      var tabInfo = $el.data('drupalOriginalTabIndices');
      if (tabInfo && tabInfo[level]) {
        var data = tabInfo[level];
        if (data.tabindex) {
          $el[0].setAttribute('tabindex', data.tabindex);
        }
        // If the element did not have a tabindex at this stack level then
        // remove it.
        else {
          $el[0].removeAttribute('tabindex');
        }
        if (data.autofocus) {
          $el[0].setAttribute('autofocus', 'autofocus');
        }

        // Clean up $.data.
        if (level === 0) {
          // Remove all data.
          $el.removeData('drupalOriginalTabIndices');
        }
        else {
          // Remove the data for this stack level and higher.
          var levelToDelete = level;
          while (tabInfo.hasOwnProperty(levelToDelete)) {
            delete tabInfo[levelToDelete];
            levelToDelete++;
          }
          $el.data('drupalOriginalTabIndices', tabInfo);
        }
      }
    }
  });

  /**
   * Stores a set of tabbable elements.
   *
   * This constraint can be removed with the release() method.
   *
   * @param Object options
   *   A set of initiating values that include:
   *   - Number level: The level in the TabbingManager's stack of this
   *   tabbingContext.
   *   - jQuery $tabbableElements: The DOM elements that should be reachable via
   *   the tab key when this tabbingContext is active.
   *   - jQuery $disabledElements: The DOM elements that should not be reachable
   *   via the tab key when this tabbingContext is active.
   *   - Boolean released: A released tabbingContext can never be activated again.
   *   It will be cleaned up when the TabbingManager unwinds its stack.
   *   - Boolean active: When true, the tabbable elements of this tabbingContext
   *   will be reachable via the tab key and the disabled elements will not. Only
   *   one tabbingContext can be active at a time.
   */
  function TabbingContext(options) {
    $.extend(this, {
      level: null,
      $tabbableElements: $(),
      $disabledElements: $(),
      released: false,
      active: false
    }, options);
  }

  /**
   * Add public methods to the TabbingContext class.
   */
  $.extend(TabbingContext.prototype, {
    /**
     * Releases this TabbingContext.
     *
     * Once a TabbingContext object is released, it can never be activated again.
     */
    release: function () {
      if (!this.released) {
        this.deactivate();
        this.released = true;
        Drupal.tabbingManager.release(this);
        // Allow modules to respond to the tabbingContext release event.
        $(document).trigger('drupalTabbingContextReleased', this);
      }
    },

    /**
     * Activates this TabbingContext.
     */
    activate: function () {
      // A released TabbingContext object can never be activated again.
      if (!this.active && !this.released) {
        this.active = true;
        Drupal.tabbingManager.activate(this);
        // Allow modules to respond to the constrain event.
        $(document).trigger('drupalTabbingContextActivated', this);
      }
    },

    /**
     * Deactivates this TabbingContext.
     */
    deactivate: function () {
      if (this.active) {
        this.active = false;
        Drupal.tabbingManager.deactivate(this);
        // Allow modules to respond to the constrain event.
        $(document).trigger('drupalTabbingContextDeactivated', this);
      }
    }
  });


  // Mark this behavior as processed on the first pass and return if it is
  // already processed.
  if (Drupal.tabbingManager) {
    return;
  }
  Drupal.tabbingManager = new TabbingManager();


}(jQuery, Drupal));
