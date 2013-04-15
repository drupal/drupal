/**
 * @file
 * A Backbone View that provides an interactive toolbar (1 per property editor).
 *
 * It listens to state changes of the property editor. It also triggers state
 * changes in response to user interactions with the toolbar, including saving.
 */
(function ($, _, Backbone, Drupal) {

"use strict";

Drupal.edit = Drupal.edit || {};
Drupal.edit.views = Drupal.edit.views || {};
Drupal.edit.views.ToolbarView = Backbone.View.extend({

  editor: null,
  $storageWidgetEl: null,

  entity: null,
  predicate : null,
  editorName: null,

  _loader: null,
  _loaderVisibleStart: 0,

  _id: null,

  events: {
    'click.edit button.label': 'onClickInfoLabel',
    'mouseleave.edit': 'onMouseLeave',
    'click.edit button.field-save': 'onClickSave',
    'click.edit button.field-close': 'onClickClose'
  },

  /**
   * Implements Backbone Views' initialize() function.
   *
   * @param options
   *   An object with the following keys:
   *   - editor: the editor object with an 'options' object that has these keys:
   *      * entity: the VIE entity for the property.
   *      * property: the predicate of the property.
   *      * editorName: the editor name.
   *      * element: the jQuery-wrapped editor DOM element
   *   - $storageWidgetEl: the DOM element on which the Create Storage widget is
   *     initialized.
   */
  initialize: function(options) {
    this.editor = options.editor;
    this.$storageWidgetEl = options.$storageWidgetEl;

    this.entity = this.editor.options.entity;
    this.predicate = this.editor.options.property;
    this.editorName = this.editor.options.editorName;

    this._loader = null;
    this._loaderVisibleStart = 0;

    // Generate a DOM-compatible ID for the toolbar DOM element.
    this._id = Drupal.edit.util.calcPropertyID(this.entity, this.predicate).replace(/\//g, '_');
  },

  /**
   * Listens to editor state changes.
   */
  stateChange: function(from, to) {
    switch (to) {
      case 'inactive':
        if (from) {
          this.remove();
          if (this.editorName !== 'form') {
            Backbone.syncDirectCleanUp();
          }
        }
        break;
      case 'candidate':
        if (from === 'inactive') {
          this.render();
        }
        else {
          if (this.editorName !== 'form') {
            Backbone.syncDirectCleanUp();
          }
          // Remove all toolgroups; they're no longer necessary.
          this.$el
            .removeClass('edit-highlighted edit-editing')
            .find('.edit-toolbar .edit-toolgroup').remove();
          if (from !== 'highlighted' && this.getEditUISetting('padding')) {
            this._unpad();
          }
        }
        break;
      case 'highlighted':
        // As soon as we highlight, make sure we have a toolbar in the DOM (with at least a title).
        this.startHighlight();
        break;
      case 'activating':
        this.setLoadingIndicator(true);
        break;
      case 'active':
        this.startEdit();
        this.setLoadingIndicator(false);
        if (this.getEditUISetting('fullWidthToolbar')) {
          this.$el.addClass('edit-toolbar-fullwidth');
        }

        if (this.getEditUISetting('padding')) {
          this._pad();
        }
        if (this.getEditUISetting('unifiedToolbar')) {
          this.insertWYSIWYGToolGroups();
        }
        break;
      case 'changed':
        this.$el
          .find('button.save')
          .addClass('blue-button')
          .removeClass('gray-button');
        break;
      case 'saving':
        this.setLoadingIndicator(true);
        this.save();
        break;
      case 'saved':
        this.setLoadingIndicator(false);
        break;
      case 'invalid':
        this.setLoadingIndicator(false);
        break;
    }
  },

  /**
   * Saves a property.
   *
   * This method deals with the complexity of the editor-dependent ways of
   * inserting updated content and showing validation error messages.
   *
   * One might argue that this does not belong in a view. However, there is no
   * actual "save" logic here, that lives in Backbone.sync. This is just some
   * glue code, along with the logic for inserting updated content as well as
   * showing validation error messages, the latter of which is certainly okay.
   */
  save: function() {
    var that = this;
    var editor = this.editor;
    var editableEntity = editor.options.widget;
    var entity = editor.options.entity;
    var predicate = editor.options.property;

    // Use Create.js' Storage widget to handle saving. (Uses Backbone.sync.)
    this.$storageWidgetEl.createStorage('saveRemote', entity, {
      editor: editor,

      // Successfully saved without validation errors.
      success: function (model) {
        editableEntity.setState('saved', predicate);

        // Now that the changes to this property have been saved, the saved
        // attributes are now the "original" attributes.
        entity._originalAttributes = entity._previousAttributes = _.clone(entity.attributes);

        // Get data necessary to rerender property before it is unavailable.
        var updatedProperty = entity.get(predicate + '/rendered');
        var $propertyWrapper = editor.element.closest('.edit-field');
        var $context = $propertyWrapper.parent();

        editableEntity.setState('candidate', predicate);
        // Unset the property, because it will be parsed again from the DOM, iff
        // its new value causes it to still be rendered.
        entity.unset(predicate, { silent: true });
        entity.unset(predicate + '/rendered', { silent: true });
        // Trigger event to allow for proper clean-up of editor-specific views.
        editor.element.trigger('destroyedPropertyEditor.edit', editor);

        // Replace the old content with the new content.
        $propertyWrapper.replaceWith(updatedProperty);
        Drupal.attachBehaviors($context);
      },

      // Save attempted but failed due to validation errors.
      error: function (validationErrorMessages) {
        editableEntity.setState('invalid', predicate);

        if (that.editorName === 'form') {
          editor.$formContainer
            .find('.edit-form')
            .addClass('edit-validation-error')
            .find('form')
            .prepend(validationErrorMessages);
        }
        else {
          var $errors = $('<div class="edit-validation-errors"></div>')
            .append(validationErrorMessages);
          editor.element
            .addClass('edit-validation-error')
            .after($errors);
        }
      }
    });
  },

  /**
   * When the user clicks the info label, nothing should happen.
   * @note currently redirects the click.edit-event to the editor DOM element.
   *
   * @param event
   */
  onClickInfoLabel: function(event) {
    event.stopPropagation();
    event.preventDefault();
    // Redirects the event to the editor DOM element.
    this.editor.element.trigger('click.edit');
  },

  /**
   * A mouseleave to the editor doesn't matter; a mouseleave to something else
   * counts as a mouseleave on the editor itself.
   *
   * @param event
   */
  onMouseLeave: function(event) {
    var el = this.editor.element[0];
    if (event.relatedTarget != el && !$.contains(el, event.relatedTarget)) {
      this.editor.element.trigger('mouseleave.edit');
    }
    event.stopPropagation();
  },

  /**
   * Upon clicking "Save", trigger a custom event to save this property.
   *
   * @param event
   */
  onClickSave: function(event) {
    event.stopPropagation();
    event.preventDefault();
    this.editor.options.widget.setState('saving', this.predicate);
  },

  /**
   * Upon clicking "Close", trigger a custom event to stop editing.
   *
   * @param event
   */
  onClickClose: function(event) {
    event.stopPropagation();
    event.preventDefault();
    this.editor.options.widget.setState('candidate', this.predicate, { reason: 'cancel' });
  },

  /**
   * Indicates in the 'info' toolgroup that we're waiting for a server reponse.
   *
   * Prevents flickering loading indicator by only showing it after 0.6 seconds
   * and if it is shown, only hiding it after another 0.6 seconds.
   *
   * @param bool enabled
   *   Whether the loading indicator should be displayed or not.
   */
  setLoadingIndicator: function(enabled) {
    var that = this;
    if (enabled) {
      this._loader = setTimeout(function() {
        that.addClass('info', 'loading');
        that._loaderVisibleStart = new Date().getTime();
      }, 600);
    }
    else {
      var currentTime = new Date().getTime();
      clearTimeout(this._loader);
      if (this._loaderVisibleStart) {
        setTimeout(function() {
          that.removeClass('info', 'loading');
        }, this._loaderVisibleStart + 600 - currentTime);
      }
      this._loader = null;
      this._loaderVisibleStart = 0;
    }
  },

  startHighlight: function() {
    // We get the label to show for this property from VIE's type system.
    var label = this.predicate;
    var attributeDef = this.entity.get('@type').attributes.get(this.predicate);
    if (attributeDef && attributeDef.metadata) {
      label = attributeDef.metadata.label;
    }

    this.$el
      .addClass('edit-highlighted')
      .find('.edit-toolbar')
      // Append the "info" toolgroup into the toolbar.
      .append(Drupal.theme('editToolgroup', {
        classes: 'info edit-animate-only-background-and-padding',
        buttons: [
          { label: label, classes: 'blank-button label' }
        ]
      }));

    // Animations.
    var that = this;
    setTimeout(function () {
      that.show('info');
    }, 0);
  },

  startEdit: function() {
    this.$el
      .addClass('edit-editing')
      .find('.edit-toolbar')
      // Append the "ops" toolgroup into the toolbar.
      .append(Drupal.theme('editToolgroup', {
        classes: 'ops',
        buttons: [
          { label: Drupal.t('Save'), type: 'submit', classes: 'field-save save gray-button' },
          { label: '<span class="close">' + Drupal.t('Close') + '</span>', classes: 'field-close close gray-button' }
        ]
      }));
    this.show('ops');
  },

  /**
   * Retrieves a setting of the editor-specific Edit UI integration.
   *
   * @see Drupal.edit.util.getEditUISetting().
   */
  getEditUISetting: function(setting) {
    return Drupal.edit.util.getEditUISetting(this.editor, setting);
  },

  /**
   * Adjusts the toolbar to accomodate padding on the PropertyEditor widget.
   *
   * @see PropertyEditorDecorationView._pad().
   */
  _pad: function() {
    // The whole toolbar must move to the top when the property's DOM element
    // is displayed inline.
    if (this.editor.element.css('display') === 'inline') {
      this.$el.css('top', parseInt(this.$el.css('top'), 10) - 5 + 'px');
    }

    // The toolbar must move to the top and the left.
    var $hf = this.$el.find('.edit-toolbar-heightfaker');
    $hf.css({ bottom: '6px', left: '-5px' });

    if (this.getEditUISetting('fullWidthToolbar')) {
      $hf.css({ width: this.editor.element.width() + 10 });
    }
  },

  /**
   * Undoes the changes made by _pad().
   *
   * @see PropertyEditorDecorationView._unpad().
   */
  _unpad: function() {
    // Move the toolbar back to its original position.
    var $hf = this.$el.find('.edit-toolbar-heightfaker');
    $hf.css({ bottom: '1px', left: '' });

    if (this.getEditUISetting('fullWidthToolbar')) {
      $hf.css({ width: '' });
    }
  },

  insertWYSIWYGToolGroups: function() {
    this.$el
      .find('.edit-toolbar')
      .append(Drupal.theme('editToolgroup', {
        id: this.getFloatedWysiwygToolgroupId(),
        classes: 'wysiwyg-floated',
        buttons: []
      }))
      .append(Drupal.theme('editToolgroup', {
        id: this.getMainWysiwygToolgroupId(),
        classes: 'wysiwyg-main',
        buttons: []
      }));

    // Animate the toolgroups into visibility.
    var that = this;
    setTimeout(function () {
      that.show('wysiwyg-floated');
      that.show('wysiwyg-main');
    }, 0);
  },

  /**
   * Renders the Toolbar's markup into the DOM.
   *
   * Note: depending on whether the 'display' property of the $el for which a
   * toolbar is being inserted into the DOM, it will be inserted differently.
   */
  render: function () {
    // Render toolbar.
    this.setElement($(Drupal.theme('editToolbarContainer', {
      id: this.getId()
    })));

    // Insert in DOM.
    if (this.editor.element.css('display') === 'inline') {
      this.$el.prependTo(this.editor.element.offsetParent());
      var pos = this.editor.element.position();
      this.$el.css('left', pos.left).css('top', pos.top);
    }
    else {
      this.$el.insertBefore(this.editor.element);
    }
  },

  /**
   * Retrieves the ID for this toolbar's container.
   *
   * Only used to make sane hovering behavior possible.
   *
   * @return string
   *   A string that can be used as the ID for this toolbar's container.
   */
  getId: function () {
    return 'edit-toolbar-for-' + this._id;
  },

  /**
   * Retrieves the ID for this toolbar's floating WYSIWYG toolgroup.
   *
   * Used to provide an abstraction for any WYSIWYG editor to plug in.
   *
   * @return string
   *   A string that can be used as the ID.
   */
  getFloatedWysiwygToolgroupId: function () {
    return 'edit-wysiwyg-floated-toolgroup-for-' + this._id;
  },

  /**
   * Retrieves the ID for this toolbar's main WYSIWYG toolgroup.
   *
   * Used to provide an abstraction for any WYSIWYG editor to plug in.
   *
   * @return string
   *   A string that can be used as the ID.
   */
  getMainWysiwygToolgroupId: function () {
    return 'edit-wysiwyg-main-toolgroup-for-' + this._id;
  },

  /**
   * Shows a toolgroup.
   *
   * @param string toolgroup
   *   A toolgroup name.
   */
  show: function (toolgroup) {
    this._find(toolgroup).removeClass('edit-animate-invisible');
  },

  /**
   * Adds classes to a toolgroup.
   *
   * @param string toolgroup
   *   A toolgroup name.
   */
  addClass: function (toolgroup, classes) {
    this._find(toolgroup).addClass(classes);
  },

  /**
   * Removes classes from a toolgroup.
   *
   * @param string toolgroup
   *   A toolgroup name.
   */
  removeClass: function (toolgroup, classes) {
    this._find(toolgroup).removeClass(classes);
  },

  /**
   * Finds a toolgroup.
   *
   * @param string toolgroup
   *   A toolgroup name.
   */
  _find: function (toolgroup) {
    return this.$el.find('.edit-toolbar .edit-toolgroup.' + toolgroup);
  }
});

})(jQuery, _, Backbone, Drupal);
