/**
 * @file
 * A Backbone Model subclass that enforces validation when calling set().
 */

(function (Backbone) {

"use strict";

Drupal.edit.BaseModel = Backbone.Model.extend({

  /**
   * {@inheritdoc}
   */
  initialize: function (options) {
    this.__initialized = true;
    return Backbone.Model.prototype.initialize.call(this, options);
  },

  /**
   * {@inheritdoc}
   */
  set: function (key, val, options) {
    if (this.__initialized) {
      // Deal with both the "key", value and {key:value}-style arguments.
      if (typeof key === 'object') {
        key.validate = true;
      }
      else {
        if (!options) {
          options = {};
        }
        options.validate = true;
      }
    }
    return Backbone.Model.prototype.set.call(this, key, val, options);
  }

});

}(Backbone));
