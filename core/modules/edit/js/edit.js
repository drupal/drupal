/**
 * @file
 * Attaches behavior for the Edit module.
 *
 * Everything happens asynchronously, to allow for:
 *   - dynamically rendered contextual links
 *   - asynchronously retrieved (and cached) per-field in-place editing metadata
 *   - asynchronous setup of in-place editable field and "Quick edit" link
 *
 * To achieve this, there are several queues:
 *   - fieldsMetadataQueue: fields whose metadata still needs to be fetched.
 *   - fieldsAvailableQueue: queue of fields whose metadata is known, and for
 *     which it has been confirmed that the user has permission to edit them.
 *     However, FieldModels will only be created for them once there's a
 *     contextual link for their entity: when it's possible to initiate editing.
 *   - contextualLinksQueue: queue of contextual links on entities for which it
 *     is not yet known whether the user has permission to edit at >=1 of them.
 */
(function ($, _, Backbone, Drupal, drupalSettings) {

"use strict";

var options = $.extend({
  strings: {
    quickEdit: Drupal.t('Quick edit'),
    stopQuickEdit: Drupal.t('Stop quick edit')
  }
}, drupalSettings.edit);

/**
 * Tracks fields without metadata. Contains objects with the following keys:
 *   - DOM el
 *   - String fieldID
 */
var fieldsMetadataQueue = [];

/**
 * Tracks fields ready for use. Contains objects with the following keys:
 *   - DOM el
 *   - String fieldID
 *   - String entityID
 */
var fieldsAvailableQueue = [];

/**
 * Tracks contextual links on entities. Contains objects with the following
 * keys:
 *   - String entityID
 *   - DOM el
 *   - DOM region
 */
var contextualLinksQueue = [];

Drupal.behaviors.edit = {
  attach: function (context) {
    // Initialize the Edit app once per page load.
    $('body').once('edit-init', initEdit);

    // Process each field element: queue to be used or to fetch metadata.
    $(context).find('[data-edit-id]').once('edit').each(function (index, fieldElement) {
      processField(fieldElement);
    });

    // Fetch metadata for any fields that are queued to retrieve it.
    fetchMissingMetadata(function (fieldElementsWithFreshMetadata) {
      // Metadata has been fetched, reprocess fields whose metadata was missing.
      _.each(fieldElementsWithFreshMetadata, processField);

      // Metadata has been fetched, try to set up more contextual links now.
      contextualLinksQueue = _.filter(contextualLinksQueue, function (contextualLink) {
        return !initializeEntityContextualLink(contextualLink);
      });
    });
  },
  detach: function (context, settings, trigger) {
    if (trigger === 'unload') {
      deleteContainedModelsAndQueues($(context));
    }
  }
};

Drupal.edit = {
  // A Drupal.edit.AppView instance.
  app: null,

  collections: {
    // All in-place editable entities (Drupal.edit.EntityModel) on the page.
    entities: null,
    // All in-place editable fields (Drupal.edit.FieldModel) on the page.
    fields: null
  },

  // In-place editors will register themselves in this object.
  editors: {},

  // Per-field metadata that indicates whether in-place editing is allowed,
  // which in-place editor should be used, etc.
  metadata: {
    has: function (fieldID) { return _.has(this.data, fieldID); },
    add: function (fieldID, metadata) { this.data[fieldID] = metadata; },
    get: function (fieldID, key) {
      return (key === undefined) ? this.data[fieldID] : this.data[fieldID][key];
    },
    intersection: function (fieldIDs) { return _.intersection(fieldIDs, _.keys(this.data)); },
    // Contains the actual metadata, keyed by field ID.
    data: {}
  }
};

/**
 * Detect contextual links on entities annotated by Edit; queue these to be
 * processed.
 */
$(document).on('drupalContextualLinkAdded', function (event, data) {
  if (data.$region.is('[data-edit-entity]')) {
    var contextualLink = {
      entityID: data.$region.attr('data-edit-entity'),
      el: data.$el[0],
      region: data.$region[0]
    };
    // Set up contextual links for this, otherwise queue it to be set up later.
    if (!initializeEntityContextualLink(contextualLink)) {
      contextualLinksQueue.push(contextualLink);
    }
  }
});

/**
 * Extracts the entity ID from a field ID.
 *
 * @param String fieldID
 *   A field ID: a string of the format
 *   `<entity type>/<id>/<field name>/<language>/<view mode>`.
 * @return String
 *   An entity ID: a string of the format `<entity type>/<id>`.
 */
function extractEntityID (fieldID) {
  return fieldID.split('/').slice(0, 2).join('/');
}

/**
 * Initialize the Edit app.
 *
 * @param DOM bodyElement
 *   This document's body element.
 */
function initEdit (bodyElement) {
  Drupal.edit.collections.entities = new Drupal.edit.EntityCollection();
  Drupal.edit.collections.fields = new Drupal.edit.FieldCollection();

  // Instantiate AppModel (application state) and AppView, which is the
  // controller of the whole in-place editing experience.
  Drupal.edit.app = new Drupal.edit.AppView({
    el: bodyElement,
    model: new Drupal.edit.AppModel(),
    entitiesCollection: Drupal.edit.collections.entities,
    fieldsCollection: Drupal.edit.collections.fields
  });
}

/**
 * Fetch the field's metadata; queue or initialize it (if EntityModel exists).
 *
 * @param DOM fieldElement
 *   A Drupal Field API field's DOM element with a data-edit-id attribute.
 */
function processField (fieldElement) {
  var metadata = Drupal.edit.metadata;
  var fieldID = fieldElement.getAttribute('data-edit-id');

  // Early-return if metadata for this field is mising.
  if (!metadata.has(fieldID)) {
    fieldsMetadataQueue.push({ el: fieldElement, fieldID: fieldID });
    return;
  }
  // Early-return if the user is not allowed to in-place edit this field.
  if (metadata.get(fieldID, 'access') !== true) {
    return;
  }

  // If an EntityModel for this field already exists (and hence also a "Quick
  // edit" contextual link), then initialize it immediately.
  var entityID = extractEntityID(fieldID);
  if (Drupal.edit.collections.entities.where({ id: entityID }).length > 0) {
    initializeField(fieldElement, fieldID);
  }
  // Otherwise: queue the field. It is now available to be set up when its
  // corresponding entity becomes in-place editable.
  else {
    fieldsAvailableQueue.push({ el: fieldElement, fieldID: fieldID, entityID: entityID });
  }
}

/**
 * Initialize a field; create FieldModel.
 *
 * @param DOM fieldElement
 *   The field's DOM element.
 * @param String fieldID
 *   The field's ID.
 */
function initializeField (fieldElement, fieldID) {
  var entityId = extractEntityID(fieldID);
  var entity = Drupal.edit.collections.entities.where({ id: entityId })[0];

  // @todo Refactor CSS to get rid of this.
  $(fieldElement).addClass('edit-field');

  // The FieldModel stores the state of an in-place editable entity field.
  var field = new Drupal.edit.FieldModel({
    el: fieldElement,
    id: fieldID,
    entity: entity,
    metadata: Drupal.edit.metadata.get(fieldID),
    acceptStateChange: _.bind(Drupal.edit.app.acceptEditorStateChange, Drupal.edit.app)
  });

  // Track all fields on the page.
  Drupal.edit.collections.fields.add(field);
}

/**
 * Fetches metadata for fields whose metadata is missing.
 *
 * Fields whose metadata is missing are tracked at fieldsMetadataQueue.
 *
 * @param Function callback
 *   A callback function that receives field elements whose metadata will just
 *   have been fetched.
 */
function fetchMissingMetadata (callback) {
  if (fieldsMetadataQueue.length) {
    var fieldIDs = _.pluck(fieldsMetadataQueue, 'fieldID');
    var fieldElementsWithoutMetadata = _.pluck(fieldsMetadataQueue, 'el');
    fieldsMetadataQueue = [];

    $.ajax({
      url: Drupal.url('edit/metadata'),
      type: 'POST',
      data: { 'fields[]' : fieldIDs },
      dataType: 'json',
      success: function(results) {
        // Store the metadata.
        _.each(results, function (fieldMetadata, fieldID) {
          Drupal.edit.metadata.add(fieldID, fieldMetadata);
        });

        callback(fieldElementsWithoutMetadata);
      }
    });
  }
}

/**
 * Loads missing in-place editor's attachments (JavaScript and CSS files).
 *
 * Missing in-place editors are those whose fields are actively being used on
 * the page but don't have
 *
 * @param Function callback
 *   Callback function to be called when the missing in-place editors (if any)
 *   have been inserted into the DOM. i.e. they may still be loading.
 */
function loadMissingEditors (callback) {
  var loadedEditors = _.keys(Drupal.edit.editors);
  var missingEditors = [];
  Drupal.edit.collections.fields.each(function (fieldModel) {
    var id = fieldModel.id;
    var metadata = Drupal.edit.metadata.get(id);
    if (metadata.access && _.indexOf(loadedEditors, metadata.editor) === -1) {
      missingEditors.push(metadata.editor);
    }
  });
  missingEditors = _.uniq(missingEditors);
  if (missingEditors.length === 0) {
    callback();
  }

  // @todo Simplify this once https://drupal.org/node/1533366 lands.
  var id = 'edit-load-editors';
  // Create a temporary element to be able to use Drupal.ajax.
  var $el = $('<div id="' + id + '" class="element-hidden"></div>').appendTo('body');
  // Create a Drupal.ajax instance to load the form.
  Drupal.ajax[id] = new Drupal.ajax(id, $el, {
    url: Drupal.url('edit/attachments'),
    event: 'edit-internal.edit',
    submit: { 'editors[]': missingEditors },
    // No progress indicator.
    progress: { type: null }
  });
  // Implement a scoped insert AJAX command: calls the callback after all AJAX
  // command functions have been executed (hence the deferred calling).
  var realInsert = Drupal.ajax.prototype.commands.insert;
  Drupal.ajax[id].commands.insert = function (ajax, response, status) {
    _.defer(function() { callback(); });
    realInsert(ajax, response, status);
  };
  // Trigger the AJAX request, which will should return AJAX commands to insert
  // any missing attachments.
  $el.trigger('edit-internal.edit');
}

/**
 * Attempts to set up a "Quick edit" link and corresponding EntityModel.
 *
 * @param Object contextualLink
 *   An object with the following properties:
 *     - String entity: an Edit entity identifier, e.g. "node/1" or
 *       "custom_block/5".
 *     - DOM el: element pointing to the contextual links placeholder for this
 *       entity.
 *     - DOM region: element pointing to the contextual region for this entity.
 * @return Boolean
 *   Returns true when a contextual the given contextual link metadata can be
 *   removed from the queue (either because the contextual link has been set up
 *   or because it is certain that in-place editing is not allowed for any of
 *   its fields).
 *   Returns false otherwise.
 */
function initializeEntityContextualLink (contextualLink) {
  var metadata = Drupal.edit.metadata;
  // Check if the user has permission to edit at least one of them.
  function hasFieldWithPermission (fieldIDs) {
    for (var i = 0; i < fieldIDs.length; i++) {
      var fieldID = fieldIDs[i];
      if (metadata.get(fieldID, 'access') === true) {
        return true;
      }
    }
    return false;
  }

  // Checks if the metadata for all given field IDs exists.
  function allMetadataExists (fieldIDs) {
    return fieldIDs.length === metadata.intersection(fieldIDs).length;
  }

  // Find all fields for this entity and collect their field IDs.
  var fields = _.where(fieldsAvailableQueue, { entityID: contextualLink.entityID });
  var fieldIDs = _.pluck(fields, 'fieldID');

  // No fields found yet.
  if (fieldIDs.length === 0) {
    return false;
  }
  // The entity for the given contextual link contains at least one field that
  // the current user may edit in-place; instantiate EntityModel and
  // ContextualLinkView.
  else if (hasFieldWithPermission(fieldIDs)) {
    var entityModel = new Drupal.edit.EntityModel({
      el: contextualLink.region,
      id: contextualLink.entityID
    });
    Drupal.edit.collections.entities.add(entityModel);

    // Initialize all queued fields within this entity (creates FieldModels).
    _.each(fields, function (field) {
      initializeField(field.el, field.fieldID);
    });
    fieldsAvailableQueue = _.difference(fieldsAvailableQueue, fields);

    // Set up contextual link view after loading any missing in-place editors.
    loadMissingEditors(function () {
      var $links = $(contextualLink.el).find('.contextual-links');
      var contextualLinkView = new Drupal.edit.ContextualLinkView($.extend({
        el: $('<li class="quick-edit"><a href=""></a></li>').prependTo($links),
        model: entityModel,
        appModel: Drupal.edit.app.model
      }, options));
      entityModel.set('contextualLinkView', contextualLinkView);
    });

    return true;
  }
  // There was not at least one field that the current user may edit in-place,
  // even though the metadata for all fields within this entity is available.
  else if (allMetadataExists(fieldIDs)) {
    return true;
  }

  return false;
}

/**
 * Delete models and queue items that are contained within a given context.
 *
 * Deletes any contained EntityModels (plus their associated FieldModels and
 * ContextualLinkView) and FieldModels, as well as the corresponding queues.
 *
 * After EntityModels, FieldModels must also be deleted, because it is possible
 * in Drupal for a field DOM element to exist outside of the entity DOM element,
 * e.g. when viewing the full node, the title of the node is not rendered within
 * the node (the entity) but as the page title.
 *
 * Note: this will not delete an entity that is actively being in-place edited.
 *
 * @param jQuery $context
 *   The context within which to delete.
 */
function deleteContainedModelsAndQueues($context) {
  $context.find('[data-edit-entity]').addBack('[data-edit-entity]').each(function (index, entityElement) {
    // Delete entity model.
    // @todo change to findWhere() as soon as we have Backbone 1.0 in Drupal
    // core.
    var entityModels = Drupal.edit.collections.entities.where({el: entityElement});
    if (entityModels.length) {
      // @todo Make this cleaner; let EntityModel.destroy() do this?
      var contextualLinkView = entityModels[0].get('contextualLinkView');
      contextualLinkView.undelegateEvents();
      contextualLinkView.remove();

      entityModels[0].destroy();
    }

    // Filter queue.
    function hasOtherRegion (contextualLink) {
      return contextualLink.region !== entityElement;
    }
    contextualLinksQueue = _.filter(contextualLinksQueue, hasOtherRegion);
  });

  $context.find('[data-edit-id]').addBack('[data-edit-id]').each(function (index, fieldElement) {
    // Delete field models.
    Drupal.edit.collections.fields.chain()
      .filter(function (fieldModel) { return fieldModel.get('el') === fieldElement; })
      .invoke('destroy');

    // Filter queues.
    function hasOtherFieldElement (field) {
      return field.el !== fieldElement;
    }
    fieldsMetadataQueue = _.filter(fieldsMetadataQueue, hasOtherFieldElement);
    fieldsAvailableQueue = _.filter(fieldsAvailableQueue, hasOtherFieldElement);
  });
}

})(jQuery, _, Backbone, Drupal, drupalSettings);
