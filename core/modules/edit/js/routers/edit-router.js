/**
 * @file
 * A Backbone Router enabling URLs to make the user enter edit mode directly.
 */
(function(Backbone, Drupal) {

"use strict";

Drupal.edit = Drupal.edit || {};
Drupal.edit.routers = {};
Drupal.edit.routers.EditRouter = Backbone.Router.extend({

  appModel: null,

  routes: {
    "edit": "edit",
    "view": "view",
    "": "view"
  },

  initialize: function(options) {
    this.appModel = options.appModel;

    var that = this;
    this.appModel.on('change:isViewing', function() {
      that.navigate(that.appModel.get('isViewing') ? '#view' : '#edit');
    });
  },

  edit: function() {
    this.appModel.set('isViewing', false);
  },

  view: function(query, page) {
    var that = this;

    // If there's an active editor, attempt to set its state to 'candidate', and
    // then act according to the user's choice.
    var activeEditor = this.appModel.get('activeEditor');
    if (activeEditor) {
      var editableEntity = activeEditor.options.widget;
      var predicate = activeEditor.options.property;
      editableEntity.setState('candidate', predicate, { reason: 'menu' }, function(accepted) {
        if (accepted) {
          that.appModel.set('isViewing', true);
        }
        else {
          that.appModel.set('isViewing', false);
        }
      });
    }
    // Otherwise, we can switch to view mode directly.
    else {
      that.appModel.set('isViewing', true);
    }
  }
});

})(Backbone, Drupal);
