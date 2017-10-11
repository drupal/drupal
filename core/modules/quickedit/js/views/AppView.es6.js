/**
 * @file
 * A Backbone View that controls the overall "in-place editing application".
 *
 * @see Drupal.quickedit.AppModel
 */

(function ($, _, Backbone, Drupal) {
  // Indicates whether the page should be reloaded after in-place editing has
  // shut down. A page reload is necessary to re-instate the original HTML of
  // the edited fields if in-place editing has been canceled and one or more of
  // the entity's fields were saved to PrivateTempStore: one of them may have
  // been changed to the empty value and hence may have been rerendered as the
  // empty string, which makes it impossible for Quick Edit to know where to
  // restore the original HTML.
  let reload = false;

  Drupal.quickedit.AppView = Backbone.View.extend(/** @lends Drupal.quickedit.AppView# */{

    /**
     * @constructs
     *
     * @augments Backbone.View
     *
     * @param {object} options
     *   An object with the following keys:
     * @param {Drupal.quickedit.AppModel} options.model
     *   The application state model.
     * @param {Drupal.quickedit.EntityCollection} options.entitiesCollection
     *   All on-page entities.
     * @param {Drupal.quickedit.FieldCollection} options.fieldsCollection
     *   All on-page fields
     */
    initialize(options) {
      // AppView's configuration for handling states.
      // @see Drupal.quickedit.FieldModel.states
      this.activeFieldStates = ['activating', 'active'];
      this.singleFieldStates = ['highlighted', 'activating', 'active'];
      this.changedFieldStates = ['changed', 'saving', 'saved', 'invalid'];
      this.readyFieldStates = ['candidate', 'highlighted'];

      // Track app state.
      this.listenTo(options.entitiesCollection, 'change:state', this.appStateChange);
      this.listenTo(options.entitiesCollection, 'change:isActive', this.enforceSingleActiveEntity);

      // Track app state.
      this.listenTo(options.fieldsCollection, 'change:state', this.editorStateChange);
      // Respond to field model HTML representation change events.
      this.listenTo(options.fieldsCollection, 'change:html', this.renderUpdatedField);
      this.listenTo(options.fieldsCollection, 'change:html', this.propagateUpdatedField);
      // Respond to addition.
      this.listenTo(options.fieldsCollection, 'add', this.rerenderedFieldToCandidate);
      // Respond to destruction.
      this.listenTo(options.fieldsCollection, 'destroy', this.teardownEditor);
    },

    /**
     * Handles setup/teardown and state changes when the active entity changes.
     *
     * @param {Drupal.quickedit.EntityModel} entityModel
     *   An instance of the EntityModel class.
     * @param {string} state
     *   The state of the associated field. One of
     *   {@link Drupal.quickedit.EntityModel.states}.
     */
    appStateChange(entityModel, state) {
      const app = this;
      let entityToolbarView;
      switch (state) {
        case 'launching':
          reload = false;
          // First, create an entity toolbar view.
          entityToolbarView = new Drupal.quickedit.EntityToolbarView({
            model: entityModel,
            appModel: this.model,
          });
          entityModel.toolbarView = entityToolbarView;
          // Second, set up in-place editors.
          // They must be notified of state changes, hence this must happen
          // while the associated fields are still in the 'inactive' state.
          entityModel.get('fields').each((fieldModel) => {
            app.setupEditor(fieldModel);
          });
          // Third, transition the entity to the 'opening' state, which will
          // transition all fields from 'inactive' to 'candidate'.
          _.defer(() => {
            entityModel.set('state', 'opening');
          });
          break;

        case 'closed':
          entityToolbarView = entityModel.toolbarView;
          // First, tear down the in-place editors.
          entityModel.get('fields').each((fieldModel) => {
            app.teardownEditor(fieldModel);
          });
          // Second, tear down the entity toolbar view.
          if (entityToolbarView) {
            entityToolbarView.remove();
            delete entityModel.toolbarView;
          }
          // A page reload may be necessary to re-instate the original HTML of
          // the edited fields.
          if (reload) {
            reload = false;
            location.reload();
          }
          break;
      }
    },

    /**
     * Accepts or reject editor (Editor) state changes.
     *
     * This is what ensures that the app is in control of what happens.
     *
     * @param {string} from
     *   The previous state.
     * @param {string} to
     *   The new state.
     * @param {null|object} context
     *   The context that is trying to trigger the state change.
     * @param {Drupal.quickedit.FieldModel} fieldModel
     *   The fieldModel to which this change applies.
     *
     * @return {bool}
     *   Whether the editor change was accepted or rejected.
     */
    acceptEditorStateChange(from, to, context, fieldModel) {
      let accept = true;

      // If the app is in view mode, then reject all state changes except for
      // those to 'inactive'.
      if (context && (context.reason === 'stop' || context.reason === 'rerender')) {
        if (from === 'candidate' && to === 'inactive') {
          accept = true;
        }
      }
      // Handling of edit mode state changes is more granular.
      else {
        // In general, enforce the states sequence. Disallow going back from a
        // "later" state to an "earlier" state, except in explicitly allowed
        // cases.
        if (!Drupal.quickedit.FieldModel.followsStateSequence(from, to)) {
          accept = false;
          // Allow: activating/active -> candidate.
          // Necessary to stop editing a field.
          if (_.indexOf(this.activeFieldStates, from) !== -1 && to === 'candidate') {
            accept = true;
          }
          // Allow: changed/invalid -> candidate.
          // Necessary to stop editing a field when it is changed or invalid.
          else if ((from === 'changed' || from === 'invalid') && to === 'candidate') {
            accept = true;
          }
          // Allow: highlighted -> candidate.
          // Necessary to stop highlighting a field.
          else if (from === 'highlighted' && to === 'candidate') {
            accept = true;
          }
          // Allow: saved -> candidate.
          // Necessary when successfully saved a field.
          else if (from === 'saved' && to === 'candidate') {
            accept = true;
          }
          // Allow: invalid -> saving.
          // Necessary to be able to save a corrected, invalid field.
          else if (from === 'invalid' && to === 'saving') {
            accept = true;
          }
          // Allow: invalid -> activating.
          // Necessary to be able to correct a field that turned out to be
          // invalid after the user already had moved on to the next field
          // (which we explicitly allow to have a fluent UX).
          else if (from === 'invalid' && to === 'activating') {
            accept = true;
          }
        }

        // If it's not against the general principle, then here are more
        // disallowed cases to check.
        if (accept) {
          let activeField;
          let activeFieldState;
          // Ensure only one field (editor) at a time is active … but allow a
          // user to hop from one field to the next, even if we still have to
          // start saving the field that is currently active: assume it will be
          // valid, to allow for a fluent UX. (If it turns out to be invalid,
          // this block of code also handles that.)
          if ((this.readyFieldStates.indexOf(from) !== -1 || from === 'invalid') && this.activeFieldStates.indexOf(to) !== -1) {
            activeField = this.model.get('activeField');
            if (activeField && activeField !== fieldModel) {
              activeFieldState = activeField.get('state');
              // Allow the state change. If the state of the active field is:
              // - 'activating' or 'active': change it to 'candidate'
              // - 'changed' or 'invalid': change it to 'saving'
              // - 'saving' or 'saved': don't do anything.
              if (this.activeFieldStates.indexOf(activeFieldState) !== -1) {
                activeField.set('state', 'candidate');
              }
              else if (activeFieldState === 'changed' || activeFieldState === 'invalid') {
                activeField.set('state', 'saving');
              }

              // If the field that's being activated is in fact already in the
              // invalid state (which can only happen because above we allowed
              // the user to move on to another field to allow for a fluent UX;
              // we assumed it would be saved successfully), then we shouldn't
              // allow the field to enter the 'activating' state, instead, we
              // simply change the active editor. All guarantees and
              // assumptions for this field still hold!
              if (from === 'invalid') {
                this.model.set('activeField', fieldModel);
                accept = false;
              }
              // Do not reject: the field is either in the 'candidate' or
              // 'highlighted' state and we allow it to enter the 'activating'
              // state!
            }
          }
          // Reject going from activating/active to candidate because of a
          // mouseleave.
          else if (_.indexOf(this.activeFieldStates, from) !== -1 && to === 'candidate') {
            if (context && context.reason === 'mouseleave') {
              accept = false;
            }
          }
          // When attempting to stop editing a changed/invalid property, ask for
          // confirmation.
          else if ((from === 'changed' || from === 'invalid') && to === 'candidate') {
            if (context && context.reason === 'mouseleave') {
              accept = false;
            }
            // Check whether the transition has been confirmed?
            else if (context && context.confirmed) {
              accept = true;
            }
          }
        }
      }

      return accept;
    },

    /**
     * Sets up the in-place editor for the given field.
     *
     * Must happen before the fieldModel's state is changed to 'candidate'.
     *
     * @param {Drupal.quickedit.FieldModel} fieldModel
     *   The field for which an in-place editor must be set up.
     */
    setupEditor(fieldModel) {
      // Get the corresponding entity toolbar.
      const entityModel = fieldModel.get('entity');
      const entityToolbarView = entityModel.toolbarView;
      // Get the field toolbar DOM root from the entity toolbar.
      const fieldToolbarRoot = entityToolbarView.getToolbarRoot();
      // Create in-place editor.
      const editorName = fieldModel.get('metadata').editor;
      const editorModel = new Drupal.quickedit.EditorModel();
      const editorView = new Drupal.quickedit.editors[editorName]({
        el: $(fieldModel.get('el')),
        model: editorModel,
        fieldModel,
      });

      // Create in-place editor's toolbar for this field — stored inside the
      // entity toolbar, the entity toolbar will position itself appropriately
      // above (or below) the edited element.
      const toolbarView = new Drupal.quickedit.FieldToolbarView({
        el: fieldToolbarRoot,
        model: fieldModel,
        $editedElement: $(editorView.getEditedElement()),
        editorView,
        entityModel,
      });

      // Create decoration for edited element: padding if necessary, sets
      // classes on the element to style it according to the current state.
      const decorationView = new Drupal.quickedit.FieldDecorationView({
        el: $(editorView.getEditedElement()),
        model: fieldModel,
        editorView,
      });

      // Track these three views in FieldModel so that we can tear them down
      // correctly.
      fieldModel.editorView = editorView;
      fieldModel.toolbarView = toolbarView;
      fieldModel.decorationView = decorationView;
    },

    /**
     * Tears down the in-place editor for the given field.
     *
     * Must happen after the fieldModel's state is changed to 'inactive'.
     *
     * @param {Drupal.quickedit.FieldModel} fieldModel
     *   The field for which an in-place editor must be torn down.
     */
    teardownEditor(fieldModel) {
      // Early-return if this field was not yet decorated.
      if (typeof fieldModel.editorView === 'undefined') {
        return;
      }

      // Unbind event handlers; remove toolbar element; delete toolbar view.
      fieldModel.toolbarView.remove();
      delete fieldModel.toolbarView;

      // Unbind event handlers; delete decoration view. Don't remove the element
      // because that would remove the field itself.
      fieldModel.decorationView.remove();
      delete fieldModel.decorationView;

      // Unbind event handlers; delete editor view. Don't remove the element
      // because that would remove the field itself.
      fieldModel.editorView.remove();
      delete fieldModel.editorView;
    },

    /**
     * Asks the user to confirm whether he wants to stop editing via a modal.
     *
     * @param {Drupal.quickedit.EntityModel} entityModel
     *   An instance of the EntityModel class.
     *
     * @see Drupal.quickedit.AppView#acceptEditorStateChange
     */
    confirmEntityDeactivation(entityModel) {
      const that = this;
      let discardDialog;

      function closeDiscardDialog(action) {
        discardDialog.close(action);
        // The active modal has been removed.
        that.model.set('activeModal', null);

        // If the targetState is saving, the field must be saved, then the
        // entity must be saved.
        if (action === 'save') {
          entityModel.set('state', 'committing', { confirmed: true });
        }
        else {
          entityModel.set('state', 'deactivating', { confirmed: true });
          // Editing has been canceled and the changes will not be saved. Mark
          // the page for reload if the entityModel declares that it requires
          // a reload.
          if (entityModel.get('reload')) {
            reload = true;
            entityModel.set('reload', false);
          }
        }
      }

      // Only instantiate if there isn't a modal instance visible yet.
      if (!this.model.get('activeModal')) {
        const $unsavedChanges = $(`<div>${Drupal.t('You have unsaved changes')}</div>`);
        discardDialog = Drupal.dialog($unsavedChanges.get(0), {
          title: Drupal.t('Discard changes?'),
          dialogClass: 'quickedit-discard-modal',
          resizable: false,
          buttons: [
            {
              text: Drupal.t('Save'),
              click() {
                closeDiscardDialog('save');
              },
              primary: true,
            },
            {
              text: Drupal.t('Discard changes'),
              click() {
                closeDiscardDialog('discard');
              },
            },
          ],
          // Prevent this modal from being closed without the user making a
          // choice as per http://stackoverflow.com/a/5438771.
          closeOnEscape: false,
          create() {
            $(this).parent().find('.ui-dialog-titlebar-close').remove();
          },
          beforeClose: false,
          close(event) {
            // Automatically destroy the DOM element that was used for the
            // dialog.
            $(event.target).remove();
          },
        });
        this.model.set('activeModal', discardDialog);

        discardDialog.showModal();
      }
    },

    /**
     * Reacts to field state changes; tracks global state.
     *
     * @param {Drupal.quickedit.FieldModel} fieldModel
     *   The `fieldModel` holding the state.
     * @param {string} state
     *   The state of the associated field. One of
     *   {@link Drupal.quickedit.FieldModel.states}.
     */
    editorStateChange(fieldModel, state) {
      const from = fieldModel.previous('state');
      const to = state;

      // Keep track of the highlighted field in the global state.
      if (_.indexOf(this.singleFieldStates, to) !== -1 && this.model.get('highlightedField') !== fieldModel) {
        this.model.set('highlightedField', fieldModel);
      }
      else if (this.model.get('highlightedField') === fieldModel && to === 'candidate') {
        this.model.set('highlightedField', null);
      }

      // Keep track of the active field in the global state.
      if (_.indexOf(this.activeFieldStates, to) !== -1 && this.model.get('activeField') !== fieldModel) {
        this.model.set('activeField', fieldModel);
      }
      else if (this.model.get('activeField') === fieldModel && to === 'candidate') {
        // Discarded if it transitions from a changed state to 'candidate'.
        if (from === 'changed' || from === 'invalid') {
          fieldModel.editorView.revert();
        }
        this.model.set('activeField', null);
      }
    },

    /**
     * Render an updated field (a field whose 'html' attribute changed).
     *
     * @param {Drupal.quickedit.FieldModel} fieldModel
     *   The FieldModel whose 'html' attribute changed.
     * @param {string} html
     *   The updated 'html' attribute.
     * @param {object} options
     *   An object with the following keys:
     * @param {bool} options.propagation
     *   Whether this change to the 'html' attribute occurred because of the
     *   propagation of changes to another instance of this field.
     */
    renderUpdatedField(fieldModel, html, options) {
      // Get data necessary to rerender property before it is unavailable.
      const $fieldWrapper = $(fieldModel.get('el'));
      const $context = $fieldWrapper.parent();

      const renderField = function () {
        // Destroy the field model; this will cause all attached views to be
        // destroyed too, and removal from all collections in which it exists.
        fieldModel.destroy();

        // Replace the old content with the new content.
        $fieldWrapper.replaceWith(html);

        // Attach behaviors again to the modified piece of HTML; this will
        // create a new field model and call rerenderedFieldToCandidate() with
        // it.
        Drupal.attachBehaviors($context.get(0));
      };

      // When propagating the changes of another instance of this field, this
      // field is not being actively edited and hence no state changes are
      // necessary. So: only update the state of this field when the rerendering
      // of this field happens not because of propagation, but because it is
      // being edited itself.
      if (!options.propagation) {
        // Deferred because renderUpdatedField is reacting to a field model
        // change event, and we want to make sure that event fully propagates
        // before making another change to the same model.
        _.defer(() => {
          // First set the state to 'candidate', to allow all attached views to
          // clean up all their "active state"-related changes.
          fieldModel.set('state', 'candidate');

          // Similarly, the above .set() call's change event must fully
          // propagate before calling it again.
          _.defer(() => {
            // Set the field's state to 'inactive', to enable the updating of
            // its DOM value.
            fieldModel.set('state', 'inactive', { reason: 'rerender' });

            renderField();
          });
        });
      }
      else {
        renderField();
      }
    },

    /**
     * Propagates changes to an updated field to all instances of that field.
     *
     * @param {Drupal.quickedit.FieldModel} updatedField
     *   The FieldModel whose 'html' attribute changed.
     * @param {string} html
     *   The updated 'html' attribute.
     * @param {object} options
     *   An object with the following keys:
     * @param {bool} options.propagation
     *   Whether this change to the 'html' attribute occurred because of the
     *   propagation of changes to another instance of this field.
     *
     * @see Drupal.quickedit.AppView#renderUpdatedField
     */
    propagateUpdatedField(updatedField, html, options) {
      // Don't propagate field updates that themselves were caused by
      // propagation.
      if (options.propagation) {
        return;
      }

      const htmlForOtherViewModes = updatedField.get('htmlForOtherViewModes');
      Drupal.quickedit.collections.fields
        // Find all instances of fields that display the same logical field
        // (same entity, same field, just a different instance and maybe a
        // different view mode).
        .where({ logicalFieldID: updatedField.get('logicalFieldID') })
        .forEach((field) => {
          // Ignore the field that was already updated.
          if (field === updatedField) {

          }
          // If this other instance of the field has the same view mode, we can
          // update it easily.
          else if (field.getViewMode() === updatedField.getViewMode()) {
            field.set('html', updatedField.get('html'));
          }
          // If this other instance of the field has a different view mode, and
          // that is one of the view modes for which a re-rendered version is
          // available (and that should be the case unless this field was only
          // added to the page after editing of the updated field began), then
          // use that view mode's re-rendered version.
          else if (field.getViewMode() in htmlForOtherViewModes) {
            field.set('html', htmlForOtherViewModes[field.getViewMode()], { propagation: true });
          }
        });
    },

    /**
     * If the new in-place editable field is for the entity that's currently
     * being edited, then transition it to the 'candidate' state.
     *
     * This happens when a field was modified, saved and hence rerendered.
     *
     * @param {Drupal.quickedit.FieldModel} fieldModel
     *   A field that was just added to the collection of fields.
     */
    rerenderedFieldToCandidate(fieldModel) {
      const activeEntity = Drupal.quickedit.collections.entities.findWhere({ isActive: true });

      // Early-return if there is no active entity.
      if (!activeEntity) {
        return;
      }

      // If the field's entity is the active entity, make it a candidate.
      if (fieldModel.get('entity') === activeEntity) {
        this.setupEditor(fieldModel);
        fieldModel.set('state', 'candidate');
      }
    },

    /**
     * EntityModel Collection change handler.
     *
     * Handler is called `change:isActive` and enforces a single active entity.
     *
     * @param {Drupal.quickedit.EntityModel} changedEntityModel
     *   The entityModel instance whose active state has changed.
     */
    enforceSingleActiveEntity(changedEntityModel) {
      // When an entity is deactivated, we don't need to enforce anything.
      if (changedEntityModel.get('isActive') === false) {
        return;
      }

      // This entity was activated; deactivate all other entities.
      changedEntityModel.collection.chain()
        .filter(entityModel => entityModel.get('isActive') === true && entityModel !== changedEntityModel)
        .each((entityModel) => {
          entityModel.set('state', 'deactivating');
        });
    },

  });
}(jQuery, _, Backbone, Drupal));
