/**
 * @file
 * A Backbone View that a dynamic contextual link.
 */
(function ($, _, Backbone, Drupal) {

"use strict";

Drupal.edit = Drupal.edit || {};
Drupal.edit.views = Drupal.edit.views || {};
Drupal.edit.views.ContextualLinkView = Backbone.View.extend({

  entity: null,

  events: {
    'click': 'onClick'
  },

  /**
   * Implements Backbone Views' initialize() function.
   *
   * @param options
   *   An object with the following keys:
   *   - entity: the entity ID (e.g. node/1) of the entity
   */
  initialize: function (options) {
    this.entity = options.entity;

    // Initial render.
    this.render();

    // Re-render whenever the app state's active entity changes.
    this.model.on('change:activeEntity', this.render, this);

    // Hide the contextual links whenever an in-place editor is active.
    this.model.on('change:activeEditor', this.toggleContextualLinksVisibility, this);
  },

  /**
   * Equates clicks anywhere on the overlay to clicking the active editor's (if
   * any) "close" button.
   *
   * @param {Object} event
   */
  onClick: function (event) {
    event.preventDefault();

    var that = this;
    var updateActiveEntity = function() {
      // The active entity is the current entity, i.e. stop editing the current
      // entity.
      if (that.model.get('activeEntity') === that.entity) {
        that.model.set('activeEntity', null);
      }
      // The active entity is different from the current entity, i.e. start
      // editing this entity instead of the previous one.
      else {
        that.model.set('activeEntity', that.entity);
      }
    };

    // If there's an active editor, attempt to set its state to 'candidate', and
    // only then do what the user asked.
    // (Only when all PropertyEditor widgets of an entity are in the 'candidate'
    // state, it is possible to stop editing it.)
    var activeEditor = this.model.get('activeEditor');
    if (activeEditor) {
      var editableEntity = activeEditor.options.widget;
      var predicate = activeEditor.options.property;
      editableEntity.setState('candidate', predicate, { reason: 'stop or switch' }, function(accepted) {
        if (accepted) {
          updateActiveEntity();
        }
        else {
          // No change.
        }
      });
    }
    // Otherwise, we can immediately do what the user asked.
    else {
      updateActiveEntity();
    }
  },

  /**
   * Render the "Quick edit" contextual link.
   */
  render: function () {
    var activeEntity = this.model.get('activeEntity');
    var string = (activeEntity !== this.entity) ? Drupal.t('Quick edit') : Drupal.t('Stop quick edit');
    this.$el.html('<a href="">' + string + '</a>');
    return this;
  },

  /**
   * Model change handler; hides the contextual links if an editor is active.
   *
   * @param Drupal.edit.models.EditAppModel model
   *   An EditAppModel model.
   * @param jQuery|null activeEditor
   *   The active in-place editor (jQuery object) or, if none, null.
   */
  toggleContextualLinksVisibility: function (model, activeEditor) {
    this.$el.parents('.contextual').toggle(activeEditor === null);
  }

});

})(jQuery, _, Backbone, Drupal);
