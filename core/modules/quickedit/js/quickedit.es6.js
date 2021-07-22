/**
 * @file
 * Attaches behavior for the Quick Edit module.
 *
 * Everything happens asynchronously, to allow for:
 *   - dynamically rendered contextual links
 *   - asynchronously retrieved (and cached) per-field in-place editing metadata
 *   - asynchronous setup of in-place editable field and "Quick edit" link.
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

(function ($, _, Backbone, Drupal, drupalSettings, JSON, storage) {
  const options = $.extend(
    drupalSettings.quickedit,
    // Merge strings on top of drupalSettings so that they are not mutable.
    {
      strings: {
        quickEdit: Drupal.t('Quick edit'),
      },
    },
  );

  /**
   * Tracks fields without metadata. Contains objects with the following keys:
   *   - DOM el
   *   - String fieldID
   *   - String entityID
   */
  let fieldsMetadataQueue = [];

  /**
   * Tracks fields ready for use. Contains objects with the following keys:
   *   - DOM el
   *   - String fieldID
   *   - String entityID
   */
  let fieldsAvailableQueue = [];

  /**
   * Tracks contextual links on entities. Contains objects with the following
   * keys:
   *   - String entityID
   *   - DOM el
   *   - DOM region
   */
  let contextualLinksQueue = [];

  /**
   * Tracks how many instances exist for each unique entity. Contains key-value
   * pairs:
   * - String entityID
   * - Number count
   */
  const entityInstancesTracker = {};

  /**
   * Initialize the Quick Edit app.
   *
   * @param {Element} bodyElement
   *   This document's body element.
   */
  function initQuickEdit(bodyElement) {
    Drupal.quickedit.collections.entities =
      new Drupal.quickedit.EntityCollection();
    Drupal.quickedit.collections.fields =
      new Drupal.quickedit.FieldCollection();

    // Instantiate AppModel (application state) and AppView, which is the
    // controller of the whole in-place editing experience.
    Drupal.quickedit.app = new Drupal.quickedit.AppView({
      el: bodyElement,
      model: new Drupal.quickedit.AppModel(),
      entitiesCollection: Drupal.quickedit.collections.entities,
      fieldsCollection: Drupal.quickedit.collections.fields,
    });
  }

  /**
   * Assigns the entity an instance ID.
   *
   * @param {HTMLElement} entityElement
   *   A Drupal Entity API entity's DOM element with a data-quickedit-entity-id
   *   attribute.
   */
  function processEntity(entityElement) {
    const entityID = entityElement.getAttribute('data-quickedit-entity-id');
    if (!entityInstancesTracker.hasOwnProperty(entityID)) {
      entityInstancesTracker[entityID] = 0;
    } else {
      entityInstancesTracker[entityID]++;
    }

    // Set the calculated entity instance ID for this element.
    const entityInstanceID = entityInstancesTracker[entityID];
    entityElement.setAttribute(
      'data-quickedit-entity-instance-id',
      entityInstanceID,
    );
  }

  /**
   * Initialize a field; create FieldModel.
   *
   * @param {HTMLElement} fieldElement
   *   The field's DOM element.
   * @param {string} fieldID
   *   The field's ID.
   * @param {string} entityID
   *   The field's entity's ID.
   * @param {string} entityInstanceID
   *   The field's entity's instance ID.
   */
  function initializeField(fieldElement, fieldID, entityID, entityInstanceID) {
    const entity = Drupal.quickedit.collections.entities.findWhere({
      entityID,
      entityInstanceID,
    });

    $(fieldElement).addClass('quickedit-field');

    // The FieldModel stores the state of an in-place editable entity field.
    const field = new Drupal.quickedit.FieldModel({
      el: fieldElement,
      fieldID,
      id: `${fieldID}[${entity.get('entityInstanceID')}]`,
      entity,
      metadata: Drupal.quickedit.metadata.get(fieldID),
      acceptStateChange: _.bind(
        Drupal.quickedit.app.acceptEditorStateChange,
        Drupal.quickedit.app,
      ),
    });

    // Track all fields on the page.
    Drupal.quickedit.collections.fields.add(field);
  }

  /**
   * Loads missing in-place editor's attachments (JavaScript and CSS files).
   *
   * Missing in-place editors are those whose fields are actively being used on
   * the page but don't have.
   *
   * @param {function} callback
   *   Callback function to be called when the missing in-place editors (if any)
   *   have been inserted into the DOM. i.e. they may still be loading.
   */
  function loadMissingEditors(callback) {
    const loadedEditors = _.keys(Drupal.quickedit.editors);
    let missingEditors = [];
    Drupal.quickedit.collections.fields.each((fieldModel) => {
      const metadata = Drupal.quickedit.metadata.get(fieldModel.get('fieldID'));
      if (metadata.access && _.indexOf(loadedEditors, metadata.editor) === -1) {
        missingEditors.push(metadata.editor);
        // Set a stub, to prevent subsequent calls to loadMissingEditors() from
        // loading the same in-place editor again. Loading an in-place editor
        // requires talking to a server, to download its JavaScript, then
        // executing its JavaScript, and only then its Drupal.quickedit.editors
        // entry will be set.
        Drupal.quickedit.editors[metadata.editor] = false;
      }
    });
    missingEditors = _.uniq(missingEditors);
    if (missingEditors.length === 0) {
      callback();
      return;
    }

    // @see https://www.drupal.org/node/2029999.
    // Create a Drupal.Ajax instance to load the form.
    const loadEditorsAjax = Drupal.ajax({
      url: Drupal.url('quickedit/attachments'),
      submit: { 'editors[]': missingEditors },
    });
    // Implement a scoped insert AJAX command: calls the callback after all AJAX
    // command functions have been executed (hence the deferred calling).
    const realInsert = Drupal.AjaxCommands.prototype.insert;
    loadEditorsAjax.commands.insert = function (ajax, response, status) {
      _.defer(callback);
      realInsert(ajax, response, status);
    };
    // Trigger the AJAX request, which will should return AJAX commands to
    // insert any missing attachments.
    loadEditorsAjax.execute();
  }

  /**
   * Attempts to set up a "Quick edit" link and corresponding EntityModel.
   *
   * @param {object} contextualLink
   *   An object with the following properties:
   *     - String entityID: a Quick Edit entity identifier, e.g. "node/1" or
   *       "block_content/5".
   *     - String entityInstanceID: a Quick Edit entity instance identifier,
   *       e.g. 0, 1 or n (depending on whether it's the first, second, or n+1st
   *       instance of this entity).
   *     - DOM el: element pointing to the contextual links placeholder for this
   *       entity.
   *     - DOM region: element pointing to the contextual region of this entity.
   *
   * @return {bool}
   *   Returns true when a contextual the given contextual link metadata can be
   *   removed from the queue (either because the contextual link has been set
   *   up or because it is certain that in-place editing is not allowed for any
   *   of its fields). Returns false otherwise.
   */
  function initializeEntityContextualLink(contextualLink) {
    const metadata = Drupal.quickedit.metadata;
    // Check if the user has permission to edit at least one of them.
    function hasFieldWithPermission(fieldIDs) {
      for (let i = 0; i < fieldIDs.length; i++) {
        const fieldID = fieldIDs[i];
        if (metadata.get(fieldID, 'access') === true) {
          return true;
        }
      }
      return false;
    }

    // Checks if the metadata for all given field IDs exists.
    function allMetadataExists(fieldIDs) {
      return fieldIDs.length === metadata.intersection(fieldIDs).length;
    }

    // Find all fields for this entity instance and collect their field IDs.
    const fields = _.where(fieldsAvailableQueue, {
      entityID: contextualLink.entityID,
      entityInstanceID: contextualLink.entityInstanceID,
    });
    const fieldIDs = _.pluck(fields, 'fieldID');

    // No fields found yet.
    if (fieldIDs.length === 0) {
      return false;
    }
    // The entity for the given contextual link contains at least one field that
    // the current user may edit in-place; instantiate EntityModel,
    // EntityDecorationView and ContextualLinkView.
    if (hasFieldWithPermission(fieldIDs)) {
      const entityModel = new Drupal.quickedit.EntityModel({
        el: contextualLink.region,
        entityID: contextualLink.entityID,
        entityInstanceID: contextualLink.entityInstanceID,
        id: `${contextualLink.entityID}[${contextualLink.entityInstanceID}]`,
        label: Drupal.quickedit.metadata.get(contextualLink.entityID, 'label'),
      });
      Drupal.quickedit.collections.entities.add(entityModel);
      // Create an EntityDecorationView associated with the root DOM node of the
      // entity.
      const entityDecorationView = new Drupal.quickedit.EntityDecorationView({
        el: contextualLink.region,
        model: entityModel,
      });
      entityModel.set('entityDecorationView', entityDecorationView);

      // Initialize all queued fields within this entity (creates FieldModels).
      _.each(fields, (field) => {
        initializeField(
          field.el,
          field.fieldID,
          contextualLink.entityID,
          contextualLink.entityInstanceID,
        );
      });
      fieldsAvailableQueue = _.difference(fieldsAvailableQueue, fields);

      // Initialization should only be called once. Use Underscore's once method
      // to get a one-time use version of the function.
      const initContextualLink = _.once(() => {
        const $links = $(contextualLink.el).find('.contextual-links');
        const contextualLinkView = new Drupal.quickedit.ContextualLinkView(
          $.extend(
            {
              el: $(
                '<li class="quickedit"><a href="" role="button" aria-pressed="false"></a></li>',
              ).prependTo($links),
              model: entityModel,
              appModel: Drupal.quickedit.app.model,
            },
            options,
          ),
        );
        entityModel.set('contextualLinkView', contextualLinkView);
      });

      // Set up ContextualLinkView after loading any missing in-place editors.
      loadMissingEditors(initContextualLink);

      return true;
    }
    // There was not at least one field that the current user may edit in-place,
    // even though the metadata for all fields within this entity is available.
    if (allMetadataExists(fieldIDs)) {
      return true;
    }

    return false;
  }

  /**
   * Extracts the entity ID from a field ID.
   *
   * @param {string} fieldID
   *   A field ID: a string of the format
   *   `<entity type>/<id>/<field name>/<language>/<view mode>`.
   *
   * @return {string}
   *   An entity ID: a string of the format `<entity type>/<id>`.
   */
  function extractEntityID(fieldID) {
    return fieldID.split('/').slice(0, 2).join('/');
  }

  /**
   * Fetch the field's metadata; queue or initialize it (if EntityModel exists).
   *
   * @param {HTMLElement} fieldElement
   *   A Drupal Field API field's DOM element with a data-quickedit-field-id
   *   attribute.
   */
  function processField(fieldElement) {
    const metadata = Drupal.quickedit.metadata;
    const fieldID = fieldElement.getAttribute('data-quickedit-field-id');
    const entityID = extractEntityID(fieldID);
    // Figure out the instance ID by looking at the ancestor
    // [data-quickedit-entity-id] element's data-quickedit-entity-instance-id
    // attribute.
    const entityElementSelector = `[data-quickedit-entity-id="${entityID}"]`;
    const $entityElement = $(entityElementSelector);

    // If there are no elements returned from `entityElementSelector`
    // throw an error. Check the browser console for this message.
    if (!$entityElement.length) {
      throw new Error(
        `Quick Edit could not associate the rendered entity field markup (with [data-quickedit-field-id="${fieldID}"]) with the corresponding rendered entity markup: no parent DOM node found with [data-quickedit-entity-id="${entityID}"]. This is typically caused by the theme's template for this entity type forgetting to print the attributes.`,
      );
    }
    let entityElement = $(fieldElement).closest($entityElement);

    // In the case of a full entity view page, the entity title is rendered
    // outside of "the entity DOM node": it's rendered as the page title. So in
    // this case, we find the lowest common parent element (deepest in the tree)
    // and consider that the entity element.
    if (entityElement.length === 0) {
      const $lowestCommonParent = $entityElement
        .parents()
        .has(fieldElement)
        .first();
      entityElement = $lowestCommonParent.find($entityElement);
    }
    const entityInstanceID = entityElement
      .get(0)
      .getAttribute('data-quickedit-entity-instance-id');

    // Early-return if metadata for this field is missing.
    if (!metadata.has(fieldID)) {
      fieldsMetadataQueue.push({
        el: fieldElement,
        fieldID,
        entityID,
        entityInstanceID,
      });
      return;
    }
    // Early-return if the user is not allowed to in-place edit this field.
    if (metadata.get(fieldID, 'access') !== true) {
      return;
    }

    // If an EntityModel for this field already exists (and hence also a "Quick
    // edit" contextual link), then initialize it immediately.
    if (
      Drupal.quickedit.collections.entities.findWhere({
        entityID,
        entityInstanceID,
      })
    ) {
      initializeField(fieldElement, fieldID, entityID, entityInstanceID);
    }
    // Otherwise: queue the field. It is now available to be set up when its
    // corresponding entity becomes in-place editable.
    else {
      fieldsAvailableQueue.push({
        el: fieldElement,
        fieldID,
        entityID,
        entityInstanceID,
      });
    }
  }

  /**
   * Delete models and queue items that are contained within a given context.
   *
   * Deletes any contained EntityModels (plus their associated FieldModels and
   * ContextualLinkView) and FieldModels, as well as the corresponding queues.
   *
   * After EntityModels, FieldModels must also be deleted, because it is
   * possible in Drupal for a field DOM element to exist outside of the entity
   * DOM element, e.g. when viewing the full node, the title of the node is not
   * rendered within the node (the entity) but as the page title.
   *
   * Note: this will not delete an entity that is actively being in-place
   * edited.
   *
   * @param {jQuery} $context
   *   The context within which to delete.
   */
  function deleteContainedModelsAndQueues($context) {
    $context
      .find('[data-quickedit-entity-id]')
      .addBack('[data-quickedit-entity-id]')
      .each((index, entityElement) => {
        // Delete entity model.
        const entityModel = Drupal.quickedit.collections.entities.findWhere({
          el: entityElement,
        });
        if (entityModel) {
          const contextualLinkView = entityModel.get('contextualLinkView');
          contextualLinkView.undelegateEvents();
          contextualLinkView.remove();
          // Remove the EntityDecorationView.
          entityModel.get('entityDecorationView').remove();
          // Destroy the EntityModel; this will also destroy its FieldModels.
          entityModel.destroy();
        }

        // Filter queue.
        function hasOtherRegion(contextualLink) {
          return contextualLink.region !== entityElement;
        }

        contextualLinksQueue = _.filter(contextualLinksQueue, hasOtherRegion);
      });

    $context
      .find('[data-quickedit-field-id]')
      .addBack('[data-quickedit-field-id]')
      .each((index, fieldElement) => {
        // Delete field models.
        Drupal.quickedit.collections.fields
          .chain()
          .filter((fieldModel) => fieldModel.get('el') === fieldElement)
          .invoke('destroy');

        // Filter queues.
        function hasOtherFieldElement(field) {
          return field.el !== fieldElement;
        }

        fieldsMetadataQueue = _.filter(
          fieldsMetadataQueue,
          hasOtherFieldElement,
        );
        fieldsAvailableQueue = _.filter(
          fieldsAvailableQueue,
          hasOtherFieldElement,
        );
      });
  }

  /**
   * Fetches metadata for fields whose metadata is missing.
   *
   * Fields whose metadata is missing are tracked at fieldsMetadataQueue.
   *
   * @param {function} callback
   *   A callback function that receives field elements whose metadata will just
   *   have been fetched.
   */
  function fetchMissingMetadata(callback) {
    if (fieldsMetadataQueue.length) {
      const fieldIDs = _.pluck(fieldsMetadataQueue, 'fieldID');
      const fieldElementsWithoutMetadata = _.pluck(fieldsMetadataQueue, 'el');
      let entityIDs = _.uniq(_.pluck(fieldsMetadataQueue, 'entityID'), true);
      // Ensure we only request entityIDs for which we don't have metadata yet.
      entityIDs = _.difference(
        entityIDs,
        Drupal.quickedit.metadata.intersection(entityIDs),
      );
      fieldsMetadataQueue = [];

      $.ajax({
        url: Drupal.url('quickedit/metadata'),
        type: 'POST',
        data: {
          'fields[]': fieldIDs,
          'entities[]': entityIDs,
        },
        dataType: 'json',
        success(results) {
          // Store the metadata.
          _.each(results, (fieldMetadata, fieldID) => {
            Drupal.quickedit.metadata.add(fieldID, fieldMetadata);
          });

          callback(fieldElementsWithoutMetadata);
        },
      });
    }
  }

  /**
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.quickedit = {
    attach(context) {
      // Initialize the Quick Edit app once per page load.
      once('quickedit-init', 'body').forEach(initQuickEdit);

      // Find all in-place editable fields, if any.
      const fields = once('quickedit', '[data-quickedit-field-id]', context);
      if (fields.length === 0) {
        return;
      }

      // Process each entity element: identical entities that appear multiple
      // times will get a numeric identifier, starting at 0.
      once('quickedit', '[data-quickedit-entity-id]', context).forEach(
        processEntity,
      );

      // Process each field element: queue to be used or to fetch metadata.
      // When a field is being rerendered after editing, it will be processed
      // immediately. New fields will be unable to be processed immediately,
      // but will instead be queued to have their metadata fetched, which occurs
      // below in fetchMissingMetaData().
      fields.forEach(processField);

      // Entities and fields on the page have been detected, try to set up the
      // contextual links for those entities that already have the necessary
      // meta- data in the client-side cache.
      contextualLinksQueue = _.filter(
        contextualLinksQueue,
        (contextualLink) => !initializeEntityContextualLink(contextualLink),
      );

      // Fetch metadata for any fields that are queued to retrieve it.
      fetchMissingMetadata((fieldElementsWithFreshMetadata) => {
        // Metadata has been fetched, reprocess fields whose metadata was
        // missing.
        _.each(fieldElementsWithFreshMetadata, processField);

        // Metadata has been fetched, try to set up more contextual links now.
        contextualLinksQueue = _.filter(
          contextualLinksQueue,
          (contextualLink) => !initializeEntityContextualLink(contextualLink),
        );
      });
    },
    detach(context, settings, trigger) {
      if (trigger === 'unload') {
        deleteContainedModelsAndQueues($(context));
      }
    },
  };

  /**
   *
   * @namespace
   */
  Drupal.quickedit = {
    /**
     * A {@link Drupal.quickedit.AppView} instance.
     */
    app: null,

    /**
     * @type {object}
     *
     * @prop {Array.<Drupal.quickedit.EntityModel>} entities
     * @prop {Array.<Drupal.quickedit.FieldModel>} fields
     */
    collections: {
      // All in-place editable entities (Drupal.quickedit.EntityModel) on the
      // page.
      entities: null,
      // All in-place editable fields (Drupal.quickedit.FieldModel) on the page.
      fields: null,
    },

    /**
     * In-place editors will register themselves in this object.
     *
     * @namespace
     */
    editors: {},

    /**
     * Per-field metadata that indicates whether in-place editing is allowed,
     * which in-place editor should be used, etc.
     *
     * @namespace
     */
    metadata: {
      /**
       * Check if a field exists in storage.
       *
       * @param {string} fieldID
       *   The field id to check.
       *
       * @return {bool}
       *   Whether it was found or not.
       */
      has(fieldID) {
        return storage.getItem(this._prefixFieldID(fieldID)) !== null;
      },

      /**
       * Add metadata to a field id.
       *
       * @param {string} fieldID
       *   The field ID to add data to.
       * @param {object} metadata
       *   Metadata to add.
       */
      add(fieldID, metadata) {
        storage.setItem(this._prefixFieldID(fieldID), JSON.stringify(metadata));
      },

      /**
       * Get a key from a field id.
       *
       * @param {string} fieldID
       *   The field ID to check.
       * @param {string} [key]
       *   The key to check. If empty, will return all metadata.
       *
       * @return {object|*}
       *   The value for the key, if defined. Otherwise will return all metadata
       *   for the specified field id.
       *
       */
      get(fieldID, key) {
        const metadata = JSON.parse(
          storage.getItem(this._prefixFieldID(fieldID)),
        );
        return typeof key === 'undefined' ? metadata : metadata[key];
      },

      /**
       * Prefix the field id.
       *
       * @param {string} fieldID
       *   The field id to prefix.
       *
       * @return {string}
       *   A prefixed field id.
       */
      _prefixFieldID(fieldID) {
        return `Drupal.quickedit.metadata.${fieldID}`;
      },

      /**
       * Unprefix the field id.
       *
       * @param {string} fieldID
       *   The field id to unprefix.
       *
       * @return {string}
       *   An unprefixed field id.
       */
      _unprefixFieldID(fieldID) {
        // Strip "Drupal.quickedit.metadata.", which is 26 characters long.
        return fieldID.substring(26);
      },

      /**
       * Intersection calculation.
       *
       * @param {Array} fieldIDs
       *   An array of field ids to compare to prefix field id.
       *
       * @return {Array}
       *   The intersection found.
       */
      intersection(fieldIDs) {
        const prefixedFieldIDs = _.map(fieldIDs, this._prefixFieldID);
        const intersection = _.intersection(
          prefixedFieldIDs,
          _.keys(sessionStorage),
        );
        return _.map(intersection, this._unprefixFieldID);
      },
    },
  };

  // Clear the Quick Edit metadata cache whenever the current user's set of
  // permissions changes.
  const permissionsHashKey =
    Drupal.quickedit.metadata._prefixFieldID('permissionsHash');
  const permissionsHashValue = storage.getItem(permissionsHashKey);
  const permissionsHash = drupalSettings.user.permissionsHash;
  if (permissionsHashValue !== permissionsHash) {
    if (typeof permissionsHash === 'string') {
      _.chain(storage)
        .keys()
        .each((key) => {
          if (key.substring(0, 26) === 'Drupal.quickedit.metadata.') {
            storage.removeItem(key);
          }
        });
    }
    storage.setItem(permissionsHashKey, permissionsHash);
  }

  /**
   * Detect contextual links on entities annotated by quickedit.
   *
   * Queue contextual links to be processed.
   *
   * @param {jQuery.Event} event
   *   The `drupalContextualLinkAdded` event.
   * @param {object} data
   *   An object containing the data relevant to the event.
   *
   * @listens event:drupalContextualLinkAdded
   */
  $(document).on('drupalContextualLinkAdded', (event, data) => {
    if (data.$region.is('[data-quickedit-entity-id]')) {
      // If the contextual link is cached on the client side, an entity instance
      // will not yet have been assigned. So assign one.
      if (!data.$region.is('[data-quickedit-entity-instance-id]')) {
        once('quickedit', data.$region);
        processEntity(data.$region.get(0));
      }
      const contextualLink = {
        entityID: data.$region.attr('data-quickedit-entity-id'),
        entityInstanceID: data.$region.attr(
          'data-quickedit-entity-instance-id',
        ),
        el: data.$el[0],
        region: data.$region[0],
      };
      // Set up contextual links for this, otherwise queue it to be set up
      // later.
      if (!initializeEntityContextualLink(contextualLink)) {
        contextualLinksQueue.push(contextualLink);
      }
    }
  });
})(
  jQuery,
  _,
  Backbone,
  Drupal,
  drupalSettings,
  window.JSON,
  window.sessionStorage,
);
