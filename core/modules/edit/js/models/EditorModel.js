(function (Backbone, Drupal) {

"use strict";

/**
 * State of an in-place editor.
 */
Drupal.edit.EditorModel = Backbone.Model.extend({

  defaults: {
    // Not the full HTML representation of this field, but the "actual"
    // original value of the field, stored by the used in-place editor, and
    // in a representation that can be chosen by the in-place editor.
    originalValue: null,
    // Analogous to originalValue, but the current value.
    currentValue: null,
    // Stores any validation errors to be rendered.
    validationErrors: null
  }

});

}(Backbone, Drupal));
