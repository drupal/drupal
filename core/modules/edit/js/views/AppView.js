(function ($, _, Backbone, Drupal, drupalSettings) {

"use strict";

// Indicates whether the page should be reloaded after in-place editing has
// shut down. A page reload is necessary to re-instate the original HTML of the
// edited fields if in-place editing has been canceled and one or more of the
// entity's fields were saved to TempStore: one of them may have been changed to
// the empty value and hence may have been rerendered as the empty string, which
// makes it impossible for Edit to know where to restore the original HTML.
var reload = false;

/**
 *
 */
Drupal.edit.AppView = Backbone.View.extend({

  /**
   * {@inheritdoc}
   *
   * @param Object options
   *   An object with the following keys:
   *   - Drupal.edit.AppModel model: the application state model
   *   - Drupal.edit.EntityCollection entitiesCollection: all on-page entities
   *   - Drupal.edit.FieldCollection fieldsCollection: all on-page fields
   */
  initialize: function (options) {
    // AppView's configuration for handling states.
    // @see Drupal.edit.FieldModel.states
    this.activeEditorStates = ['activating', 'active'];
    this.singleEditorStates = ['highlighted', 'activating', 'active'];
    this.changedEditorStates = ['changed', 'saving', 'saved', 'invalid'];
    this.fieldReadyStates = ['candidate', 'highlighted'];

    options.entitiesCollection
      // Track app state.
      .on('change:state', this.appStateChange, this)
      .on('change:isActive', this.enforceSingleActiveEntity, this);

    options.fieldsCollection
      // Track app state.
      .on('change:state', this.editorStateChange, this)
      // Respond to field model HTML representation change events.
      .on('change:html', this.renderUpdatedField, this)
      // Respond to addition.
      .on('add', this.rerenderedFieldToCandidate, this)
      // Respond to destruction.
      .on('destroy', this.teardownEditor, this);
  },

  /**
   * Handles setup/teardown and state changes when the active entity changes.
   *
   * @param Drupal.edit.EntityModel entityModel
   *   An instance of the EntityModel class.
   * @param String state
   *   The state of the associated field. One of Drupal.edit.EntityModel.states.
   */
  appStateChange: function (entityModel, state) {
    var app = this;
    var entityToolbarView;
    switch (state) {
      case 'launching':
        reload = false;
        // First, create an entity toolbar view.
        entityToolbarView = new Drupal.edit.EntityToolbarView({
          model: entityModel,
          appModel: this.model
        });
        entityModel.toolbarView = entityToolbarView;
        // Second, set up in-place editors.
        // They must be notified of state changes, hence this must happen while
        // the associated fields are still in the 'inactive' state.
        entityModel.get('fields').each(function (fieldModel) {
          app.setupEditor(fieldModel);
        });
        // Third, transition the entity to the 'opening' state, which will
        // transition all fields from 'inactive' to 'candidate'.
        _.defer(function () {
          entityModel.set('state', 'opening');
        });
        break;
      case 'closed':
        entityToolbarView = entityModel.toolbarView;
        // First, tear down the in-place editors.
        entityModel.get('fields').each(function (fieldModel) {
          app.teardownEditor(fieldModel);
        });
        // Second, tear down the entity toolbar view.
        if (entityToolbarView) {
          entityToolbarView.remove();
          delete entityModel.toolbarView;
        }
        // A page reload may be necessary to re-instate the original HTML of the
        // edited fields.
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
   * @param String from
   *   The previous state.
   * @param String to
   *   The new state.
   * @param null|Object context
   *   The context that is trying to trigger the state change.
   * @param Drupal.edit.FieldModel fieldModel
   *   The fieldModel to which this change applies.
   */
  acceptEditorStateChange: function (from, to, context, fieldModel) {
    var accept = true;

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
      if (!Drupal.edit.FieldModel.followsStateSequence(from, to)) {
        accept = false;
        // Allow: activating/active -> candidate.
        // Necessary to stop editing a field.
        if (_.indexOf(this.activeEditorStates, from) !== -1 && to === 'candidate') {
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
        // Necessary to be able to correct a field that turned out to be invalid
        // after the user already had moved on to the next field (which we
        // explicitly allow to have a fluent UX).
        else if (from === 'invalid' && to === 'activating') {
          accept = true;
        }
      }

      // If it's not against the general principle, then here are more
      // disallowed cases to check.
      if (accept) {
        var activeEditor, activeEditorState;
        // Ensure only one editor (field) at a time is active … but allow a user
        // to hop from one field to the next, even if we still have to start
        // saving the field that is currently active: assume it will be valid,
        // to allow for a fluent UX. (If it turns out to be invalid, this block
        // of code also handles that.)
        if ((this.fieldReadyStates.indexOf(from) !== -1 || from === 'invalid') && this.activeEditorStates.indexOf(to) !== -1) {
          activeEditor = this.model.get('activeEditor');
          if (activeEditor && activeEditor !== fieldModel) {
            activeEditorState = activeEditor.get('state');
            // Allow the state change. If the state of the active editor is:
            // - 'activating' or 'active': change it to 'candidate'
            // - 'changed' or 'invalid': change it to 'saving'
            // - 'saving'or 'saved': don't do anything.
            if (this.activeEditorStates.indexOf(activeEditorState) !== -1) {
              activeEditor.set('state', 'candidate');
            }
            else if (activeEditorState === 'changed' || activeEditorState === 'invalid') {
              activeEditor.set('state', 'saving');
            }

            // If the field that's being activated is in fact already in the
            // invalid state (which can only happen because above we allowed the
            // user to move on to another field to allow for a fluent UX; we
            // assumed it would be saved successfully), then we shouldn't allow
            // the field to enter the 'activating' state, instead, we simply
            // change the active editor. All guarantees and assumptions for this
            // field still hold!
            if (from === 'invalid') {
              this.model.set('activeEditor', fieldModel);
              accept = false;
            }
            else {
              // Do not reject: the field is either in the 'candidate' or
              // 'highlighted' state and we allow it to enter the 'activating'
              // state!
            }
          }
        }
        // Reject going from activating/active to candidate because of a
        // mouseleave.
        else if (_.indexOf(this.activeEditorStates, from) !== -1 && to === 'candidate') {
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
          else {
            // Check whether the transition has been confirmed?
            if (context && context.confirmed) {
              accept = true;
            }
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
   * @param Drupal.edit.FieldModel fieldModel
   *   The field for which an in-place editor must be set up.
   */
  setupEditor: function (fieldModel) {
    // Get the corresponding entity toolbar.
    var entityModel = fieldModel.get('entity');
    var entityToolbarView = entityModel.toolbarView;
    // Get the field toolbar DOM root from the entity toolbar.
    var fieldToolbarRoot = entityToolbarView.getToolbarRoot();
    // Create in-place editor.
    var editorName = fieldModel.get('metadata').editor;
    var editorModel = new Drupal.edit.EditorModel();
    var editorView = new Drupal.edit.editors[editorName]({
      el: $(fieldModel.get('el')),
      model: editorModel,
      fieldModel: fieldModel
    });

    // Create in-place editor's toolbar — positions appropriately above the
    // edited element.
    var toolbarView = new Drupal.edit.FieldToolbarView({
      el: fieldToolbarRoot,
      model: fieldModel,
      $editedElement: $(editorView.getEditedElement()),
      editorView: editorView,
      entityModel: entityModel
    });

    // Create decoration for edited element: padding if necessary, sets classes
    // on the element to style it according to the current state.
    var decorationView = new Drupal.edit.EditorDecorationView({
      el: $(editorView.getEditedElement()),
      model: fieldModel,
      editorView: editorView
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
   * @param Drupal.edit.FieldModel fieldModel
   *   The field for which an in-place editor must be torn down.
   */
  teardownEditor: function (fieldModel) {
    // Early-return if this field was not yet decorated.
    if (fieldModel.editorView === undefined) {
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
   * @see acceptEditorStateChange()
   */
  confirmEntityDeactivation: function (entityModel) {
    var that = this;
    var discardDialog;

    function closeDiscardDialog (action) {
      discardDialog.close(action);
      // The active modal has been removed.
      that.model.set('activeModal', null);

      // If the targetState is saving, the field must be saved, then the
      // entity must be saved.
      if (action === 'save') {
        entityModel.set('state', 'committing', {confirmed : true});
      }
      else {
        entityModel.set('state', 'deactivating', {confirmed : true});
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
      discardDialog = Drupal.dialog('<div>' + Drupal.t('You have unsaved changes') + '</div>', {
        title: Drupal.t('Discard changes?'),
        dialogClass: 'edit-discard-modal',
        resizable: false,
        buttons: [
          {
            text: Drupal.t('Save'),
            click: function() {
              closeDiscardDialog('save');
            }
          },
          {
            text: Drupal.t('Discard changes'),
            click: function() {
              closeDiscardDialog('discard');
            }
          }
        ],
        // Prevent this modal from being closed without the user making a choice
        // as per http://stackoverflow.com/a/5438771.
        closeOnEscape: false,
        create: function () {
          $(this).parent().find('.ui-dialog-titlebar-close').remove();
        },
        beforeClose: false
      });
      this.model.set('activeModal', discardDialog);

      discardDialog.showModal();
    }
  },

  /**
   * Reacts to field state changes; tracks global state.
   *
   * @param Drupal.edit.FieldModel fieldModel
   * @param String state
   *   The state of the associated field. One of Drupal.edit.FieldModel.states.
   */
  editorStateChange: function (fieldModel, state) {
    var from = fieldModel.previous('state');
    var to = state;

    // Keep track of the highlighted editor in the global state.
    if (_.indexOf(this.singleEditorStates, to) !== -1 && this.model.get('highlightedEditor') !== fieldModel) {
      this.model.set('highlightedEditor', fieldModel);
    }
    else if (this.model.get('highlightedEditor') === fieldModel && to === 'candidate') {
      this.model.set('highlightedEditor', null);
    }

    // Keep track of the active editor in the global state.
    if (_.indexOf(this.activeEditorStates, to) !== -1 && this.model.get('activeEditor') !== fieldModel) {
      this.model.set('activeEditor', fieldModel);
    }
    else if (this.model.get('activeEditor') === fieldModel && to === 'candidate') {
      // Discarded if it transitions from a changed state to 'candidate'.
      if (from === 'changed' || from === 'invalid') {
        fieldModel.editorView.revert();
      }
      this.model.set('activeEditor', null);
    }
  },

  /**
   *
   */
  enableEditor: function (fieldModel) {
    // check if there's an active editor.
    var activeEditor = this.model.get('activeEditor');

    // Do nothing if the fieldModel is already the active editor.
    if (fieldModel === activeEditor) {
      return;
    }
    if (activeEditor) {
      // If there is, check if the model is changed.
      if (activeEditor.get('state') === 'changed') {
        // Attempt to save the field.
        activeEditor.set('state', 'saving');
      }
      // else, set it to a candidate.
      else {
        activeEditor.set('state', 'candidate');
        // Set the new fieldModel to activating.
        fieldModel.set('state', 'activating');
      }
    }
    else {
      // Set the new fieldModel to activating.
      fieldModel.set('state', 'activating');
    }
  },

  /**
   * Render an updated field (a field whose 'html' attribute changed).
   *
   * @param Drupal.edit.FieldModel fieldModel
   *   The FieldModel whose 'html' attribute changed.
   */
  renderUpdatedField: function (fieldModel) {
    // Get data necessary to rerender property before it is unavailable.
    var html = fieldModel.get('html');
    var $fieldWrapper = $(fieldModel.get('el'));
    var $context = $fieldWrapper.parent();

    // First set the state to 'candidate', to allow all attached views to
    // clean up all their "active state"-related changes.
    fieldModel.set('state', 'candidate');

    // Set the field's state to 'inactive', to enable the updating of its DOM
    // value.
    fieldModel.set('state', 'inactive', { reason: 'rerender' });

    // Destroy the field model; this will cause all attached views to be
    // destroyed too, and removal from all collections in which it exists.
    fieldModel.destroy();

    // Replace the old content with the new content.
    $fieldWrapper.replaceWith(html);

    // Attach behaviors again to the modified piece of HTML; this will create
    // a new field model and call rerenderedFieldToCandidate() with it.
    Drupal.attachBehaviors($context);
  },

  /**
   * If the new in-place editable field is for the entity that's currently
   * being edited, then transition it to the 'candidate' state.
   *
   * This happens when a field was modified, saved and hence rerendered.
   *
   * @param Drupal.edit.FieldModel fieldModel
   *   A field that was just added to the collection of fields.
   */
  rerenderedFieldToCandidate: function (fieldModel) {
    var activeEntity = Drupal.edit.collections.entities.where({isActive: true})[0];

    // Early-return if there is no active entity.
    if (activeEntity === null) {
      return;
    }

    // If the field's entity is the active entity, make it a candidate.
    if (fieldModel.get('entity') === activeEntity) {
      this.setupEditor(fieldModel);
      fieldModel.set('state', 'candidate');
    }
  },

  /**
   * EntityModel Collection change handler, called on change:isActive, enforces
   * a single active entity.
   *
   * @param Drupal.edit.EntityModel
   *   The entityModel instance whose active state has changed.
   */
  enforceSingleActiveEntity: function (changedEntityModel) {
    // When an entity is deactivated, we don't need to enforce anything.
    if (changedEntityModel.get('isActive') === false) {
      return;
    }

    // This entity was activated; deactivate all other entities.
    changedEntityModel.collection.chain()
      .filter(function (entityModel) {
        return entityModel.get('isActive') === true && entityModel !== changedEntityModel;
      })
      .each(function (entityModel) {
        entityModel.set('state', 'deactivating');
      });
  }
});

}(jQuery, _, Backbone, Drupal, drupalSettings));
