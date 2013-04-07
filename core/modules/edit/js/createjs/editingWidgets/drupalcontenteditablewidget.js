/**
 * @file
 * Override of Create.js' default "base" (plain contentEditable) widget.
 */
(function (jQuery, Drupal) {

"use strict";

  // @todo D8: use jQuery UI Widget bridging.
  // @see http://drupal.org/node/1874934#comment-7124904
  jQuery.widget('DrupalEditEditor.direct', jQuery.Create.editWidget, {

    /**
     * Implements getEditUISettings() method.
     */
    getEditUISettings: function() {
      return { padding: true, unifiedToolbar: false, fullWidthToolbar: false };
    },

    /**
     * Implements jQuery UI widget factory's _init() method.
     *
     * @todo: POSTPONED_ON(Create.js, https://github.com/bergie/create/issues/142)
     * Get rid of this once that issue is solved.
     */
    _init: function() {},

    /**
     * Implements Create's _initialize() method.
     */
    _initialize: function() {
      var that = this;

      // Sets the state to 'changed' whenever the content has changed.
      var before = jQuery.trim(this.element.text());
      this.element.on('keyup paste', function (event) {
        if (that.options.disabled) {
          return;
        }
        var current = jQuery.trim(that.element.text());
        if (before !== current) {
          before = current;
          that.options.changed(current);
        }
      });
    },

    /**
     * Makes this PropertyEditor widget react to state changes.
     */
    stateChange: function(from, to) {
      switch (to) {
        case 'inactive':
          break;
        case 'candidate':
          if (from !== 'inactive') {
            // Removes the "contenteditable" attribute.
            this.disable();
          }
          break;
        case 'highlighted':
          break;
        case 'activating':
          this.options.activated();
          break;
        case 'active':
          // Sets the "contenteditable" attribute to "true".
          this.enable();
          break;
        case 'changed':
          break;
        case 'saving':
          break;
        case 'saved':
          break;
        case 'invalid':
          break;
      }
    }

  });

})(jQuery, Drupal);
