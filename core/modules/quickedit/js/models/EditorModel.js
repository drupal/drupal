/**
 * @file
 * A Backbone Model for the state of an in-place editor.
 *
 * @see Drupal.quickedit.EditorView
 */

(function (Backbone, Drupal) {

  "use strict";

  Drupal.quickedit.EditorModel = Backbone.Model.extend({

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
