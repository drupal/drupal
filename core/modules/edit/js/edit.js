/**
 * @file
 * Attaches behavior for the Edit module.
 */
(function ($, _, Backbone, Drupal, drupalSettings) {

"use strict";

Drupal.edit = { metadataCache: {}, contextualLinksQueue: [] };

/**
 * Attach toggling behavior and in-place editing.
 */
Drupal.behaviors.edit = {
  attach: function (context) {
    var $context = $(context);
    var $fields = $context.find('[data-edit-id]');

    // Initialize the Edit app.
    $('body').once('edit-init', Drupal.edit.init);

    function annotateField (field) {
      var hasField = _.has(Drupal.edit.metadataCache, field.editID);
      if (hasField) {
        var meta = Drupal.edit.metadataCache[field.editID];

        field.$el.addClass((meta.access) ? 'edit-allowed' : 'edit-disallowed');
        if (meta.access) {
          field.$el
            .attr('data-edit-field-label', meta.label)
            .attr('aria-label', meta.aria)
            .addClass('edit-field edit-type-' + ((meta.editor === 'form') ? 'form' : 'direct'));
        }
      }
      return hasField;
    }

    // Find all fields in the context without metadata.
    var fieldsToAnnotate = _.map($fields.not('.edit-allowed, .edit-disallowed'), function (el) {
      var $el = $(el);
      return { $el: $el, editID: $el.attr('data-edit-id') };
    });

    // Fields whose metadata is known (typically when they were just modified)
    // can be annotated immediately, those remaining must be requested.
    var remainingFieldsToAnnotate = _.reduce(fieldsToAnnotate, function (result, field) {
      if (!annotateField(field)) {
        result.push(field);
      }
      return result;
    }, []);

    // Make fields that could be annotated immediately available for editing.
    Drupal.edit.app.findEditableProperties($context);

    if (remainingFieldsToAnnotate.length) {
      $(window).ready(function () {
        var id = 'edit-load-metadata';
        // Create a temporary element to be able to use Drupal.ajax.
        var $el = jQuery('<div id="' + id + '" class="element-hidden"></div>').appendTo('body');
        // Create a Drupal.ajax instance to load the form.
        Drupal.ajax[id] = new Drupal.ajax(id, $el, {
          url: drupalSettings.edit.metadataURL,
          event: 'edit-internal.edit',
          submit: { 'fields[]': _.pluck(remainingFieldsToAnnotate, 'editID') },
          // No progress indicator.
          progress: { type: null }
        });
        // Implement a scoped editMetaData AJAX command: calls the callback.
        Drupal.ajax[id].commands.editMetadata = function (ajax, response, status) {
          // Update the metadata cache.
          _.each(response.data, function (metadata, editID) {
            Drupal.edit.metadataCache[editID] = metadata;
          });

          // Annotate the remaining fields based on the updated access cache.
          _.each(remainingFieldsToAnnotate, annotateField);

          // Find editable fields, make them editable.
          Drupal.edit.app.findEditableProperties($context);

          // Metadata cache has been updated, try to set up more contextual
          // links now.
          Drupal.edit.contextualLinksQueue = _.filter(Drupal.edit.contextualLinksQueue, function (data) {
            return !Drupal.edit.setUpContextualLink(data);
          });

          // Delete the Drupal.ajax instance that called this very function.
          delete Drupal.ajax[id];

          // Also delete the temporary element.
          // $el.remove();
        };
        // This will ensure our scoped editMetadata AJAX command gets called.
        $el.trigger('edit-internal.edit');
      });
    }
  }
};

/**
 * Detect contextual links on entities annotated by Edit; queue these to be
 * processed.
 */
$(document).on('drupalContextualLinkAdded', function (event, data) {
  if (data.$region.is('[data-edit-entity]')) {
    var contextualLink = {
      entity: data.$region.attr('data-edit-entity'),
      $el: data.$el,
      $region: data.$region
    };
    // Set up contextual links for this, otherwise queue it to be set up later.
    if (!Drupal.edit.setUpContextualLink(contextualLink)) {
      Drupal.edit.contextualLinksQueue.push(contextualLink);
    }
  }
});

/**
 * Attempts to set up a "Quick edit" contextual link.
 *
 * @param Object contextualLink
 *   An object with the following properties:
 *     - entity: an Edit entity identifier, e.g. "node/1" or "custom_block/5".
 *     - $el: a jQuery element pointing to the contextual links for this entity.
 *     - $region: a jQuery element pointing to the contextual region for this
 *       entity.
 *
 * @return Boolean
 *   Returns true when a contextual the given contextual link metadata can be
 *   removed from the queue (either because the contextual link has been set up
 *   or because it is certain that in-place editing is not allowed for any of
 *   its fields).
 *   Returns false otherwise.
 */
Drupal.edit.setUpContextualLink = function (contextualLink) {
  // Check if the user has permission to edit at least one of them.
  function hasFieldWithPermission (editIDs) {
    var i, meta = Drupal.edit.metadataCache;
    for (i = 0; i < editIDs.length; i++) {
      var editID = editIDs[i];
      if (_.has(meta, editID) && meta[editID].access === true) {
        return true;
      }
    }
    return false;
  }

  // Checks if the metadata for all given editIDs exists.
  function allMetadataExists (editIDs) {
    var editIDsWithMetadata = _.intersection(editIDs, _.keys(Drupal.edit.metadataCache));
    return editIDs.length === editIDsWithMetadata.length;
  }

  // Find the Edit IDs of all fields within this entity.
  var editIDs = [];
  contextualLink.$region
    .find('[data-edit-id^="' + contextualLink.entity + '/"]')
    .each(function () {
      editIDs.push($(this).attr('data-edit-id'));
    });

  // The entity for the given contextual link contains at least one field that
  // the current user may edit in-place; instantiate ContextualLinkView.
  if (hasFieldWithPermission(editIDs)) {
    new Drupal.edit.views.ContextualLinkView({
      el: $('<li class="quick-edit"><a href=""></a></li>').prependTo(contextualLink.$el),
      model: Drupal.edit.app.model,
      entity: contextualLink.entity
    });
    return true;
  }
  // There was not at least one field that the current user may edit in-place,
  // even though the metadata for all fields within this entity is available.
  else if (allMetadataExists(editIDs)) {
    return true;
  }

  return false;
};

Drupal.edit.init = function () {
  // Instantiate EditAppView, which is the controller of it all. EditAppModel
  // instance tracks global state (viewing/editing in-place).
  var appModel = new Drupal.edit.models.EditAppModel();
  // For now, we work with a singleton app, because for Drupal.behaviors to be
  // able to discover new editable properties that get AJAXed in, it must know
  // with which app instance they should be associated.
  Drupal.edit.app = new Drupal.edit.EditAppView({
    el: $('body'),
    model: appModel
  });
};

})(jQuery, _, Backbone, Drupal, drupalSettings);
