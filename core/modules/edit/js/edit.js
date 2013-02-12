/**
 * @file
 * Attaches behavior for the Edit module.
 */
(function ($, _, Backbone, Drupal, drupalSettings) {

"use strict";

Drupal.edit = Drupal.edit || {};
Drupal.edit.metadataCache = Drupal.edit.metadataCache || {};

/**
 * Attach toggling behavior and in-place editing.
 */
Drupal.behaviors.edit = {
  attach: function(context) {
    var $context = $(context);
    var $fields = $context.find('[data-edit-id]');

    // Initialize the Edit app.
    $('body').once('edit-init', Drupal.edit.init);

    var annotateField = function(field) {
      if (_.has(Drupal.edit.metadataCache, field.editID)) {
        var meta = Drupal.edit.metadataCache[field.editID];

        field.$el.addClass((meta.access) ? 'edit-allowed' : 'edit-disallowed');
        if (meta.access) {
          field.$el
            .attr('data-edit-field-label', meta.label)
            .attr('aria-label', meta.aria)
            .addClass('edit-field edit-type-' + ((meta.editor === 'form') ? 'form' : 'direct'));
        }

        return true;
      }
      return false;
    };

    // Find all fields in the context without metadata.
    var fieldsToAnnotate = _.map($fields.not('.edit-allowed, .edit-disallowed'), function(el) {
      var $el = $(el);
      return { $el: $el, editID: $el.attr('data-edit-id') };
    });

    // Fields whose metadata is known (typically when they were just modified)
    // can be annotated immediately, those remaining must be requested.
    var remainingFieldsToAnnotate = _.reduce(fieldsToAnnotate, function(result, field) {
      if (!annotateField(field)) {
        result.push(field);
      }
      return result;
    }, []);

    // Make fields that could be annotated immediately available for editing.
    Drupal.edit.app.findEditableProperties($context);

    if (remainingFieldsToAnnotate.length) {
      $(window).ready(function() {
        $.ajax({
          url: drupalSettings.edit.metadataURL,
          type: 'POST',
          data: { 'fields[]' : _.pluck(remainingFieldsToAnnotate, 'editID') },
          dataType: 'json',
          success: function(results) {
            // Update the metadata cache.
            _.each(results, function(metadata, editID) {
              Drupal.edit.metadataCache[editID] = metadata;
            });

            // Annotate the remaining fields based on the updated access cache.
            _.each(remainingFieldsToAnnotate, annotateField);

            // Find editable fields, make them editable.
            Drupal.edit.app.findEditableProperties($context);
          }
        });
      });
    }
  }
};

Drupal.edit.init = function() {
  // Instantiate EditAppView, which is the controller of it all. EditAppModel
  // instance tracks global state (viewing/editing in-place).
  var appModel = new Drupal.edit.models.EditAppModel();
  var app = new Drupal.edit.EditAppView({
    el: $('body'),
    model: appModel
  });

  // Add "Quick edit" links to all contextual menus where editing the full
  // node is possible.
  // @todo Generalize this to work for all entities.
  $('ul.contextual-links li.node-edit')
  .before('<li class="quick-edit"></li>')
  .each(function() {
    // Instantiate ContextualLinkView.
    var $editContextualLink = $(this).prev();
    var editContextualLinkView = new Drupal.edit.views.ContextualLinkView({
      el: $editContextualLink.get(0),
      model: appModel,
      entity: $editContextualLink.parents('[data-edit-entity]').attr('data-edit-entity')
    });
  });

  // For now, we work with a singleton app, because for Drupal.behaviors to be
  // able to discover new editable properties that get AJAXed in, it must know
  // with which app instance they should be associated.
  Drupal.edit.app = app;
};

})(jQuery, _, Backbone, Drupal, drupalSettings);
