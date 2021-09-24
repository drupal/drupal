/**
 * @file
 * A Backbone Model for the state of an in-place editable entity in the DOM.
 */

(function(_, $, Backbone, Drupal) {
  Drupal.quickedit.EntityModel = Drupal.quickedit.BaseModel.extend(
    /** @lends Drupal.quickedit.EntityModel# */ {
      /**
       * @type {object}
       */
      defaults: /** @lends Drupal.quickedit.EntityModel# */ {
        /**
         * The DOM element that represents this entity.
         *
         * It may seem bizarre to have a DOM element in a Backbone Model, but we
         * need to be able to map entities in the DOM to EntityModels in memory.
         *
         * @type {HTMLElement}
         */
        el: null,

        /**
         * An entity ID, of the form `<entity type>/<entity ID>`
         *
         * @example
         * "node/1"
         *
         * @type {string}
         */
        entityID: null,

        /**
         * An entity instance ID.
         *
         * The first instance of a specific entity (i.e. with a given entity ID)
         * is assigned 0, the second 1, and so on.
         *
         * @type {number}
         */
        entityInstanceID: null,

        /**
         * The unique ID of this entity instance on the page, of the form
         * `<entity type>/<entity ID>[entity instance ID]`
         *
         * @example
         * "node/1[0]"
         *
         * @type {string}
         */
        id: null,

        /**
         * The label of the entity.
         *
         * @type {string}
         */
        label: null,

        /**
         * A FieldCollection for all fields of the entity.
         *
         * @type {Drupal.quickedit.FieldCollection}
         *
         * @see Drupal.quickedit.FieldCollection
         */
        fields: null,

        // The attributes below are stateful. The ones above will never change
        // during the life of a EntityModel instance.

        /**
         * Indicates whether this entity is currently being edited in-place.
         *
         * @type {bool}
         */
        isActive: false,

        /**
         * Whether one or more fields are already been stored in PrivateTempStore.
         *
         * @type {bool}
         */
        inTempStore: false,

        /**
         * Indicates whether a "Save" button is necessary or not.
         *
         * Whether one or more fields have already been stored in PrivateTempStore
         * *or* the field that's currently being edited is in the 'changed' or a
         * later state.
         *
         * @type {bool}
         */
        isDirty: false,

        /**
         * Whether the request to the server has been made to commit this entity.
         *
         * Used to prevent multiple such requests.
         *
         * @type {bool}
         */
        isCommitting: false,

        /**
         * The current processing state of an entity.
         *
         * @type {string}
         */
        state: 'closed',

        /**
         * IDs of fields whose new values have been stored in PrivateTempStore.
         *
         * We must store this on the EntityModel as well (even though it already
         * is on the FieldModel) because when a field is rerendered, its
         * FieldModel is destroyed and this allows us to transition it back to
         * the proper state.
         *
         * @type {Array.<string>}
         */
        fieldsInTempStore: [],

        /**
         * A flag the tells the application that this EntityModel must be reloaded
         * in order to restore the original values to its fields in the client.
         *
         * @type {bool}
         */
        reload: false,
      },

      /**
       * @constructs
       *
       * @augments Drupal.quickedit.BaseModel
       */
      initialize() {
        this.set('fields', new Drupal.quickedit.FieldCollection());

        // Respond to entity state changes.
        this.listenTo(this, 'change:state', this.stateChange);

        // The state of the entity is largely dependent on the state of its
        // fields.
        this.listenTo(
          this.get('fields'),
          'change:state',
          this.fieldStateChange,
        );

        // Call Drupal.quickedit.BaseModel's initialize() method.
        Drupal.quickedit.BaseModel.prototype.initialize.call(this);
      },

      /**
       * Updates FieldModels' states when an EntityModel change occurs.
       *
       * @param {Drupal.quickedit.EntityModel} entityModel
       *   The entity model
       * @param {string} state
       *   The state of the associated entity. One of
       *   {@link Drupal.quickedit.EntityModel.states}.
       * @param {object} options
       *   Options for the entity model.
       */
      stateChange(entityModel, state, options) {
        const to = state;
        switch (to) {
          case 'closed':
            this.set({
              isActive: false,
              inTempStore: false,
              isDirty: false,
            });
            break;

          case 'launching':
            break;

          case 'opening':
            // Set the fields to candidate state.
            entityModel.get('fields').each(fieldModel => {
              fieldModel.set('state', 'candidate', options);
            });
            break;

          case 'opened':
            // The entity is now ready for editing!
            this.set('isActive', true);
            break;

          case 'committing': {
            // The user indicated they want to save the entity.
            const fields = this.get('fields');
            // For fields that are in an active state, transition them to
            // candidate.
            fields
              .chain()
              .filter(
                fieldModel =>
                  _.intersection([fieldModel.get('state')], ['active']).length,
              )
              .each(fieldModel => {
                fieldModel.set('state', 'candidate');
              });
            // For fields that are in a changed state, field values must first be
            // stored in PrivateTempStore.
            fields
              .chain()
              .filter(
                fieldModel =>
                  _.intersection(
                    [fieldModel.get('state')],
                    Drupal.quickedit.app.changedFieldStates,
                  ).length,
              )
              .each(fieldModel => {
                fieldModel.set('state', 'saving');
              });
            break;
          }

          case 'deactivating': {
            const changedFields = this.get('fields').filter(
              fieldModel =>
                _.intersection(
                  [fieldModel.get('state')],
                  ['changed', 'invalid'],
                ).length,
            );
            // If the entity contains unconfirmed or unsaved changes, return the
            // entity to an opened state and ask the user if they would like to
            // save the changes or discard the changes.
            //   1. One of the fields is in a changed state. The changed field
            //   might just be a change in the client or it might have been saved
            //   to tempstore.
            //   2. The saved flag is empty and the confirmed flag is empty. If
            //   the entity has been saved to the server, the fields changed in
            //   the client are irrelevant. If the changes are confirmed, then
            //   proceed to set the fields to candidate state.
            if (
              (changedFields.length || this.get('fieldsInTempStore').length) &&
              !options.saved &&
              !options.confirmed
            ) {
              // Cancel deactivation until the user confirms save or discard.
              this.set('state', 'opened', { confirming: true });
              // An action in reaction to state change must be deferred.
              _.defer(() => {
                Drupal.quickedit.app.confirmEntityDeactivation(entityModel);
              });
            } else {
              const invalidFields = this.get('fields').filter(
                fieldModel =>
                  _.intersection([fieldModel.get('state')], ['invalid']).length,
              );
              // Indicate if this EntityModel needs to be reloaded in order to
              // restore the original values of its fields.
              entityModel.set(
                'reload',
                this.get('fieldsInTempStore').length || invalidFields.length,
              );
              // Set all fields to the 'candidate' state. A changed field may have
              // to go through confirmation first.
              entityModel.get('fields').each(fieldModel => {
                // If the field is already in the candidate state, trigger a
                // change event so that the entityModel can move to the next state
                // in deactivation.
                if (
                  _.intersection(
                    [fieldModel.get('state')],
                    ['candidate', 'highlighted'],
                  ).length
                ) {
                  fieldModel.trigger(
                    'change:state',
                    fieldModel,
                    fieldModel.get('state'),
                    options,
                  );
                } else {
                  fieldModel.set('state', 'candidate', options);
                }
              });
            }
            break;
          }

          case 'closing':
            // Set all fields to the 'inactive' state.
            options.reason = 'stop';
            this.get('fields').each(fieldModel => {
              fieldModel.set(
                {
                  inTempStore: false,
                  state: 'inactive',
                },
                options,
              );
            });
            break;
        }
      },

      /**
       * Updates a Field and Entity model's "inTempStore" when appropriate.
       *
       * Helper function.
       *
       * @param {Drupal.quickedit.EntityModel} entityModel
       *   The model of the entity for which a field's state attribute has
       *   changed.
       * @param {Drupal.quickedit.FieldModel} fieldModel
       *   The model of the field whose state attribute has changed.
       *
       * @see Drupal.quickedit.EntityModel#fieldStateChange
       */
      _updateInTempStoreAttributes(entityModel, fieldModel) {
        const current = fieldModel.get('state');
        const previous = fieldModel.previous('state');
        let fieldsInTempStore = entityModel.get('fieldsInTempStore');
        // If the fieldModel changed to the 'saved' state: remember that this
        // field was saved to PrivateTempStore.
        if (current === 'saved') {
          // Mark the entity as saved in PrivateTempStore, so that we can pass the
          // proper "reset PrivateTempStore" boolean value when communicating with
          // the server.
          entityModel.set('inTempStore', true);
          // Mark the field as saved in PrivateTempStore, so that visual
          // indicators signifying just that may be rendered.
          fieldModel.set('inTempStore', true);
          // Remember that this field is in PrivateTempStore, restore when
          // rerendered.
          fieldsInTempStore.push(fieldModel.get('fieldID'));
          fieldsInTempStore = _.uniq(fieldsInTempStore);
          entityModel.set('fieldsInTempStore', fieldsInTempStore);
        }
        // If the fieldModel changed to the 'candidate' state from the
        // 'inactive' state, then this is a field for this entity that got
        // rerendered. Restore its previous 'inTempStore' attribute value.
        else if (current === 'candidate' && previous === 'inactive') {
          fieldModel.set(
            'inTempStore',
            _.intersection([fieldModel.get('fieldID')], fieldsInTempStore)
              .length > 0,
          );
        }
      },

      /**
       * Reacts to state changes in this entity's fields.
       *
       * @param {Drupal.quickedit.FieldModel} fieldModel
       *   The model of the field whose state attribute changed.
       * @param {string} state
       *   The state of the associated field. One of
       *   {@link Drupal.quickedit.FieldModel.states}.
       */
      fieldStateChange(fieldModel, state) {
        const entityModel = this;
        const fieldState = state;
        // Switch on the entityModel state.
        // The EntityModel responds to FieldModel state changes as a function of
        // its state. For example, a field switching back to 'candidate' state
        // when its entity is in the 'opened' state has no effect on the entity.
        // But that same switch back to 'candidate' state of a field when the
        // entity is in the 'committing' state might allow the entity to proceed
        // with the commit flow.
        switch (this.get('state')) {
          case 'closed':
          case 'launching':
            // It should be impossible to reach these: fields can't change state
            // while the entity is closed or still launching.
            break;

          case 'opening':
            // We must change the entity to the 'opened' state, but it must first
            // be confirmed that all of its fieldModels have transitioned to the
            // 'candidate' state.
            // We do this here, because this is called every time a fieldModel
            // changes state, hence each time this is called, we get closer to the
            // goal of having all fieldModels in the 'candidate' state.
            // A state change in reaction to another state change must be
            // deferred.
            _.defer(() => {
              entityModel.set('state', 'opened', {
                'accept-field-states': Drupal.quickedit.app.readyFieldStates,
              });
            });
            break;

          case 'opened':
            // Set the isDirty attribute when appropriate so that it is known when
            // to display the "Save" button in the entity toolbar.
            // Note that once a field has been changed, there's no way to discard
            // that change, hence it will have to be saved into PrivateTempStore,
            // or the in-place editing of this field will have to be stopped
            // completely. In other words: once any field enters the 'changed'
            // field, then for the remainder of the in-place editing session, the
            // entity is by definition dirty.
            if (fieldState === 'changed') {
              entityModel.set('isDirty', true);
            } else {
              this._updateInTempStoreAttributes(entityModel, fieldModel);
            }
            break;

          case 'committing': {
            // If the field save returned a validation error, set the state of the
            // entity back to 'opened'.
            if (fieldState === 'invalid') {
              // A state change in reaction to another state change must be
              // deferred.
              _.defer(() => {
                entityModel.set('state', 'opened', { reason: 'invalid' });
              });
            } else {
              this._updateInTempStoreAttributes(entityModel, fieldModel);
            }

            // Attempt to save the entity. If the entity's fields are not yet all
            // in a ready state, the save will not be processed.
            const options = {
              'accept-field-states': Drupal.quickedit.app.readyFieldStates,
            };
            if (entityModel.set('isCommitting', true, options)) {
              entityModel.save({
                success() {
                  entityModel.set(
                    {
                      state: 'deactivating',
                      isCommitting: false,
                    },
                    { saved: true },
                  );
                },
                error() {
                  // Reset the "isCommitting" mutex.
                  entityModel.set('isCommitting', false);
                  // Change the state back to "opened", to allow the user to hit
                  // the "Save" button again.
                  entityModel.set('state', 'opened', {
                    reason: 'networkerror',
                  });
                  // Show a modal to inform the user of the network error.
                  const message = Drupal.t(
                    'Your changes to <q>@entity-title</q> could not be saved, either due to a website problem or a network connection problem.<br>Please try again.',
                    { '@entity-title': entityModel.get('label') },
                  );
                  Drupal.quickedit.util.networkErrorModal(
                    Drupal.t('Network problem!'),
                    message,
                  );
                },
              });
            }
            break;
          }

          case 'deactivating':
            // When setting the entity to 'closing', require that all fieldModels
            // are in either the 'candidate' or 'highlighted' state.
            // A state change in reaction to another state change must be
            // deferred.
            _.defer(() => {
              entityModel.set('state', 'closing', {
                'accept-field-states': Drupal.quickedit.app.readyFieldStates,
              });
            });
            break;

          case 'closing':
            // When setting the entity to 'closed', require that all fieldModels
            // are in the 'inactive' state.
            // A state change in reaction to another state change must be
            // deferred.
            _.defer(() => {
              entityModel.set('state', 'closed', {
                'accept-field-states': ['inactive'],
              });
            });
            break;
        }
      },

      /**
       * Fires an AJAX request to the REST save URL for an entity.
       *
       * @param {object} options
       *   An object of options that contains:
       * @param {function} [options.success]
       *   A function to invoke if the entity is successfully saved.
       */
      save(options) {
        const entityModel = this;

        // Create a Drupal.ajax instance to save the entity.
        const entitySaverAjax = Drupal.ajax({
          url: Drupal.url(`quickedit/entity/${entityModel.get('entityID')}`),
          error() {
            // Let the Drupal.quickedit.EntityModel Backbone model's error()
            // method handle errors.
            options.error.call(entityModel);
          },
        });
        // Entity saved successfully.
        entitySaverAjax.commands.quickeditEntitySaved = function(
          ajax,
          response,
          status,
        ) {
          // All fields have been moved from PrivateTempStore to permanent
          // storage, update the "inTempStore" attribute on FieldModels, on the
          // EntityModel and clear EntityModel's "fieldInTempStore" attribute.
          entityModel.get('fields').each(fieldModel => {
            fieldModel.set('inTempStore', false);
          });
          entityModel.set('inTempStore', false);
          entityModel.set('fieldsInTempStore', []);

          // Invoke the optional success callback.
          if (options.success) {
            options.success.call(entityModel);
          }
        };
        entitySaverAjax.options.headers = entitySaverAjax.options.headers || {};
        entitySaverAjax.options.headers['X-Drupal-Quickedit-CSRF-Token'] =
          drupalSettings.quickedit.csrf_token;
        // Trigger the AJAX request, which will will return the
        // quickeditEntitySaved AJAX command to which we then react.
        entitySaverAjax.execute();
      },

      /**
       * Validate the entity model.
       *
       * @param {object} attrs
       *   The attributes changes in the save or set call.
       * @param {object} options
       *   An object with the following option:
       * @param {string} [options.reason]
       *   A string that conveys a particular reason to allow for an exceptional
       *   state change.
       * @param {Array} options.accept-field-states
       *   An array of strings that represent field states that the entities must
       *   be in to validate. For example, if `accept-field-states` is
       *   `['candidate', 'highlighted']`, then all the fields of the entity must
       *   be in either of these two states for the save or set call to
       *   validate and proceed.
       *
       * @return {string}
       *   A string to say something about the state of the entity model.
       */
      validate(attrs, options) {
        const acceptedFieldStates = options['accept-field-states'] || [];

        // Validate state change.
        const currentState = this.get('state');
        const nextState = attrs.state;
        if (currentState !== nextState) {
          // Ensure it's a valid state.
          if (_.indexOf(this.constructor.states, nextState) === -1) {
            return `"${nextState}" is an invalid state`;
          }

          // Ensure it's a state change that is allowed.
          // Check if the acceptStateChange function accepts it.
          if (!this._acceptStateChange(currentState, nextState, options)) {
            return 'state change not accepted';
          }
          // If that function accepts it, then ensure all fields are also in an
          // acceptable state.
          if (!this._fieldsHaveAcceptableStates(acceptedFieldStates)) {
            return 'state change not accepted because fields are not in acceptable state';
          }
        }

        // Validate setting isCommitting = true.
        const currentIsCommitting = this.get('isCommitting');
        const nextIsCommitting = attrs.isCommitting;
        if (currentIsCommitting === false && nextIsCommitting === true) {
          if (!this._fieldsHaveAcceptableStates(acceptedFieldStates)) {
            return 'isCommitting change not accepted because fields are not in acceptable state';
          }
        } else if (currentIsCommitting === true && nextIsCommitting === true) {
          return 'isCommitting is a mutex, hence only changes are allowed';
        }
      },

      /**
       * Checks if a state change can be accepted.
       *
       * @param {string} from
       *   From state.
       * @param {string} to
       *   To state.
       * @param {object} context
       *   Context for the check.
       * @param {string} context.reason
       *   The reason for the state change.
       * @param {bool} context.confirming
       *   Whether context is confirming or not.
       *
       * @return {bool}
       *   Whether the state change is accepted or not.
       *
       * @see Drupal.quickedit.AppView#acceptEditorStateChange
       */
      _acceptStateChange(from, to, context) {
        let accept = true;

        // In general, enforce the states sequence. Disallow going back from a
        // "later" state to an "earlier" state, except in explicitly allowed
        // cases.
        if (!this.constructor.followsStateSequence(from, to)) {
          accept = false;

          // Allow: closing -> closed.
          // Necessary to stop editing an entity.
          if (from === 'closing' && to === 'closed') {
            accept = true;
          }
          // Allow: committing -> opened.
          // Necessary to be able to correct an invalid field, or to hit the
          // "Save" button again after a server/network error.
          else if (
            from === 'committing' &&
            to === 'opened' &&
            context.reason &&
            (context.reason === 'invalid' || context.reason === 'networkerror')
          ) {
            accept = true;
          }
          // Allow: deactivating -> opened.
          // Necessary to be able to confirm changes with the user.
          else if (
            from === 'deactivating' &&
            to === 'opened' &&
            context.confirming
          ) {
            accept = true;
          }
          // Allow: opened -> deactivating.
          // Necessary to be able to stop editing.
          else if (
            from === 'opened' &&
            to === 'deactivating' &&
            context.confirmed
          ) {
            accept = true;
          }
        }

        return accept;
      },

      /**
       * Checks if fields have acceptable states.
       *
       * @param {Array} acceptedFieldStates
       *   An array of acceptable field states to check for.
       *
       * @return {bool}
       *   Whether the fields have an acceptable state.
       *
       * @see Drupal.quickedit.EntityModel#validate
       */
      _fieldsHaveAcceptableStates(acceptedFieldStates) {
        let accept = true;

        // If no acceptable field states are provided, assume all field states are
        // acceptable. We want to let validation pass as a default and only
        // check validity on calls to set that explicitly request it.
        if (acceptedFieldStates.length > 0) {
          const fieldStates = this.get('fields').pluck('state') || [];
          // If not all fields are in one of the accepted field states, then we
          // still can't allow this state change.
          if (_.difference(fieldStates, acceptedFieldStates).length) {
            accept = false;
          }
        }

        return accept;
      },

      /**
       * Destroys the entity model.
       *
       * @param {object} options
       *   Options for the entity model.
       */
      destroy(options) {
        Drupal.quickedit.BaseModel.prototype.destroy.call(this, options);

        this.stopListening();

        // Destroy all fields of this entity.
        this.get('fields').reset();
      },

      /**
       * {@inheritdoc}
       */
      sync() {
        // We don't use REST updates to sync.
      },
    },
    /** @lends Drupal.quickedit.EntityModel */ {
      /**
       * Sequence of all possible states an entity can be in during quickediting.
       *
       * @type {Array.<string>}
       */
      states: [
        // Initial state, like field's 'inactive' OR the user has just finished
        // in-place editing this entity.
        // - Trigger: none (initial) or EntityModel (finished).
        // - Expected behavior: (when not initial state): tear down
        //   EntityToolbarView, in-place editors and related views.
        'closed',
        // User has activated in-place editing of this entity.
        // - Trigger: user.
        // - Expected behavior: the EntityToolbarView is gets set up, in-place
        //   editors (EditorViews) and related views for this entity's fields are
        //   set up. Upon completion of those, the state is changed to 'opening'.
        'launching',
        // Launching has finished.
        // - Trigger: application.
        // - Guarantees: in-place editors ready for use, all entity and field
        //   views have been set up, all fields are in the 'inactive' state.
        // - Expected behavior: all fields are changed to the 'candidate' state
        //   and once this is completed, the entity state will be changed to
        //   'opened'.
        'opening',
        // Opening has finished.
        // - Trigger: EntityModel.
        // - Guarantees: see 'opening', all fields are in the 'candidate' state.
        // - Expected behavior: the user is able to actually use in-place editing.
        'opened',
        // User has clicked the 'Save' button (and has thus changed at least one
        // field).
        // - Trigger: user.
        // - Guarantees: see 'opened', plus: either a changed field is in
        //   PrivateTempStore, or the user has just modified a field without
        //   activating (switching to) another field.
        // - Expected behavior: 1) if any of the fields are not yet in
        //   PrivateTempStore, save them to PrivateTempStore, 2) if then any of
        //   the fields has the 'invalid' state, then change the entity state back
        //   to 'opened', otherwise: save the entity by committing it from
        //   PrivateTempStore into permanent storage.
        'committing',
        // User has clicked the 'Close' button, or has clicked the 'Save' button
        // and that was successfully completed.
        // - Trigger: user or EntityModel.
        // - Guarantees: when having clicked 'Close' hardly any: fields may be in
        //   a variety of states; when having clicked 'Save': all fields are in
        //   the 'candidate' state.
        // - Expected behavior: transition all fields to the 'candidate' state,
        //   possibly requiring confirmation in the case of having clicked
        //   'Close'.
        'deactivating',
        // Deactivation has been completed.
        // - Trigger: EntityModel.
        // - Guarantees: all fields are in the 'candidate' state.
        // - Expected behavior: change all fields to the 'inactive' state.
        'closing',
      ],

      /**
       * Indicates whether the 'from' state comes before the 'to' state.
       *
       * @param {string} from
       *   One of {@link Drupal.quickedit.EntityModel.states}.
       * @param {string} to
       *   One of {@link Drupal.quickedit.EntityModel.states}.
       *
       * @return {bool}
       *   Whether the 'from' state comes before the 'to' state.
       */
      followsStateSequence(from, to) {
        return _.indexOf(this.states, from) < _.indexOf(this.states, to);
      },
    },
  );

  /**
   * @constructor
   *
   * @augments Backbone.Collection
   */
  Drupal.quickedit.EntityCollection = Backbone.Collection.extend(
    /** @lends Drupal.quickedit.EntityCollection# */ {
      /**
       * @type {Drupal.quickedit.EntityModel}
       */
      model: Drupal.quickedit.EntityModel,
    },
  );
})(_, jQuery, Backbone, Drupal);
