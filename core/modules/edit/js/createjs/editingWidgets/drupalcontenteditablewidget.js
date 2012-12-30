/**
 * @file
 * Override of Create.js' default "base" (plain contentEditable) widget.
 */
(function (jQuery, Drupal) {

"use strict";

  jQuery.widget('Drupal.drupalContentEditableWidget', jQuery.Create.editWidget, {

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

      // Sets the state to 'activated' upon clicking the element.
      this.element.on("click.edit", function(event) {
        event.stopPropagation();
        event.preventDefault();
        that.options.activated();
      });

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
            this._removeValidationErrors();
            this._cleanUp();
          }
          break;
        case 'highlighted':
          break;
        case 'activating':
          break;
        case 'active':
          // Sets the "contenteditable" attribute to "true".
          this.enable();
          break;
        case 'changed':
          break;
        case 'saving':
          this._removeValidationErrors();
          break;
        case 'saved':
          break;
        case 'invalid':
          break;
      }
    },

    /**
     * Removes validation errors' markup changes, if any.
     *
     * Note: this only needs to happen for type=direct, because for type=direct,
     * the property DOM element itself is modified; this is not the case for
     * type=form.
     */
    _removeValidationErrors: function() {
      this.element
        .removeClass('edit-validation-error')
        .next('.edit-validation-errors').remove();
    },

    /**
     * Cleans up after the widget has been saved.
     *
     * Note: this is where the Create.Storage and accompanying Backbone.sync
     * abstractions "leak" implementation details. That is only the case because
     * we have to use Drupal's Form API as a transport mechanism. It is
     * unfortunately a stateful transport mechanism, and that's why we have to
     * clean it up here. This clean-up is only necessary when canceling the
     * editing of a property after having attempted to save at least once.
     */
    _cleanUp: function() {
      Drupal.edit.util.form.unajaxifySaving(jQuery('#edit_backstage form .edit-form-submit'));
      jQuery('#edit_backstage form').remove();
    }
  });

})(jQuery, Drupal);
