/**
 * @file
 * A Backbone View that provides an interactive toolbar (1 per in-place editor).
 */
(function ($, _, Backbone, Drupal) {

"use strict";

Drupal.edit.FieldToolbarView = Backbone.View.extend({

  // The edited element, as indicated by EditorView.getEditedElement().
  $editedElement: null,

  // A reference to the in-place editor.
  editorView: null,

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
   * {@inheritdoc}
   */
  initialize: function (options) {
    this.$editedElement = options.$editedElement;
    this.editorView = options.editorView;

    this._loader = null;
    this._loaderVisibleStart = 0;

    // Generate a DOM-compatible ID for the form container DOM element.
    this._id = 'edit-toolbar-for-' + this.model.id.replace(/\//g, '_');

    this.model.on('change:state', this.stateChange, this);
  },

  /**
   * {@inheritdoc}
   */
  render: function () {
    // Render toolbar.
    this.setElement($(Drupal.theme('editToolbarContainer', {
      id: this._id
    })));

    // Insert in DOM.
    if (this.$editedElement.css('display') === 'inline') {
      this.$el.prependTo(this.$editedElement.offsetParent());
      var pos = this.$editedElement.position();
      this.$el.css('left', pos.left).css('top', pos.top);
    }
    else {
      this.$el.insertBefore(this.$editedElement);
    }

    return this;
  },

  /**
   * Determines the actions to take given a change of state.
   *
   * @param Drupal.edit.FieldModel model
   * @param String state
   *   The state of the associated field. One of Drupal.edit.FieldModel.states.
   */
  stateChange: function (model, state) {
    var from = model.previous('state');
    var to = state;
    switch (to) {
      case 'inactive':
        if (from) {
          this.remove();
        }
        break;
      case 'candidate':
        if (from === 'inactive') {
          this.render();
        }
        else {
          // Remove all toolgroups; they're no longer necessary.
          this.$el
            .removeClass('edit-highlighted edit-editing')
            .find('.edit-toolbar .edit-toolgroup').remove();
          if (from !== 'highlighted' && this.editorView.getEditUISettings().padding) {
            this._unpad();
          }
        }
        break;
      case 'highlighted':
        // As soon as we highlight, make sure we have a toolbar in the DOM (with
        // at least a title).
        this.startHighlight();
        break;
      case 'activating':
        this.setLoadingIndicator(true);
        break;
      case 'active':
        this.startEdit();
        this.setLoadingIndicator(false);
        if (this.editorView.getEditUISettings().fullWidthToolbar) {
          this.$el.addClass('edit-toolbar-fullwidth');
        }

        if (this.editorView.getEditUISettings().padding) {
          this._pad();
        }
        if (this.editorView.getEditUISettings().unifiedToolbar) {
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
   * Redirects the click.edit-event to the editor DOM element.
   *
   * @param jQuery event
   */
  onClickInfoLabel: function (event) {
    event.stopPropagation();
    event.preventDefault();
    // Redirects the event to the editor DOM element.
    this.$editedElement.trigger('click.edit');
  },

  /**
   * Controls mouseleave events.
   *
   * A mouseleave to the editor doesn't matter; a mouseleave to something else
   * counts as a mouseleave on the editor itself.
   *
   * @param jQuery event
   */
  onMouseLeave: function (event) {
    if (event.relatedTarget !== this.$editedElement[0] && !$.contains(this.$editedElement, event.relatedTarget)) {
      this.$editedElement.trigger('mouseleave.edit');
    }
    event.stopPropagation();
  },

  /**
   * Set the model state to 'saving' when the save button is clicked.
   *
   * @param jQuery event
   */
  onClickSave: function (event) {
    event.stopPropagation();
    event.preventDefault();
    this.model.set('state', 'saving');
  },

  /**
   * Sets the model state to candidate when the cancel button is clicked.
   *
   * @param jQuery event
   */
  onClickClose: function (event) {
    event.stopPropagation();
    event.preventDefault();
    this.model.set('state', 'candidate', { reason: 'cancel' });
  },

  /**
   * Indicates in the 'info' toolgroup that we're waiting for a server reponse.
   *
   * Prevents flickering loading indicator by only showing it after 0.6 seconds
   * and if it is shown, only hiding it after another 0.6 seconds.
   *
   * @param Boolean enabled
   *   Whether the loading indicator should be displayed or not.
   */
  setLoadingIndicator: function (enabled) {
    var that = this;
    if (enabled) {
      this._loader = setTimeout(function () {
        that.addClass('info', 'loading');
        that._loaderVisibleStart = new Date().getTime();
      }, 600);
    }
    else {
      var currentTime = new Date().getTime();
      clearTimeout(this._loader);
      if (this._loaderVisibleStart) {
        setTimeout(function () {
          that.removeClass('info', 'loading');
        }, this._loaderVisibleStart + 600 - currentTime);
      }
      this._loader = null;
      this._loaderVisibleStart = 0;
    }
  },

  /**
   * Decorate the field with markup to indicate it is highlighted.
   */
  startHighlight: function () {
    // Retrieve the lavel to show for this field.
    var label = this.model.get('metadata').label;

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

  /**
   * Decorate the field with markup to indicate edit state; append a toolbar.
   */
  startEdit: function () {
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
   * Adjusts the toolbar to accomodate padding on the editor.
   *
   * @see EditorDecorationView._pad().
   */
  _pad: function () {
    // The whole toolbar must move to the top when the property's DOM element
    // is displayed inline.
    if (this.$editedElement.css('display') === 'inline') {
      this.$el.css('top', parseInt(this.$el.css('top'), 10) - 5 + 'px');
    }

    // The toolbar must move to the top and the left.
    var $hf = this.$el.find('.edit-toolbar-heightfaker');
    $hf.css({ bottom: '6px', left: '-5px' });

    if (this.editorView.getEditUISettings().fullWidthToolbar) {
      $hf.css({ width: this.$editedElement.width() + 10 });
    }
  },

  /**
   * Undoes the changes made by _pad().
   *
   * @see EditorDecorationView._unpad().
   */
  _unpad: function () {
    // Move the toolbar back to its original position.
    var $hf = this.$el.find('.edit-toolbar-heightfaker');
    $hf.css({ bottom: '1px', left: '' });

    if (this.editorView.getEditUISettings().fullWidthToolbar) {
      $hf.css({ width: '' });
    }
  },

  /**
   * Insert WYSIWYG markup into the associated toolbar.
   */
  insertWYSIWYGToolGroups: function () {
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
   * Retrieves the ID for this toolbar's container.
   *
   * Only used to make sane hovering behavior possible.
   *
   * @return String
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
   * @return String
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
   * @return String
   *   A string that can be used as the ID.
   */
  getMainWysiwygToolgroupId: function () {
    return 'edit-wysiwyg-main-toolgroup-for-' + this._id;
  },

  /**
   * Shows a toolgroup.
   *
   * @param String toolgroup
   *   A toolgroup name.
   */
  show: function (toolgroup) {
    this._find(toolgroup).removeClass('edit-animate-invisible');
  },

  /**
   * Adds classes to a toolgroup.
   *
   * @param String toolgroup
   *   A toolgroup name.
   * @param String classes
   *   A space delimited list of class names to add to the toolgroup.
   */
  addClass: function (toolgroup, classes) {
    this._find(toolgroup).addClass(classes);
  },

  /**
   * Removes classes from a toolgroup.
   *
   * @param String toolgroup
   *   A toolgroup name.
   * @param String classes
   *   A space delimited list of class names to remove from the toolgroup.
   */
  removeClass: function (toolgroup, classes) {
    this._find(toolgroup).removeClass(classes);
  },

  /**
   * Finds a toolgroup.
   *
   * @param String toolgroup
   *   A toolgroup name.
   * @return jQuery
   */
  _find: function (toolgroup) {
    return this.$el.find('.edit-toolbar .edit-toolgroup.' + toolgroup);
  }
});

})(jQuery, _, Backbone, Drupal);
