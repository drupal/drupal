/**
 * @file
 * A Backbone View that decorates a Property Editor widget.
 *
 * It listens to state changes of the property editor.
 */
(function($, Backbone, Drupal) {

"use strict";

Drupal.edit = Drupal.edit || {};
Drupal.edit.views = Drupal.edit.views || {};
Drupal.edit.views.PropertyEditorDecorationView = Backbone.View.extend({

  toolbarId: null,

  _widthAttributeIsEmpty: null,

  events: {
    'mouseenter.edit' : 'onMouseEnter',
    'mouseleave.edit' : 'onMouseLeave',
    'click': 'onClick',
    'tabIn.edit': 'onMouseEnter',
    'tabOut.edit': 'onMouseLeave'
  },

  /**
   * Implements Backbone Views' initialize() function.
   *
   * @param options
   *   An object with the following keys:
   *   - editor: the editor object with an 'options' object that has these keys:
   *      * entity: the VIE entity for the property.
   *      * property: the predicate of the property.
   *      * widget: the parent EditableEntity widget.
   *      * editorName: the name of the PropertyEditor widget
   *   - toolbarId: the ID attribute of the toolbar as rendered in the DOM.
   */
  initialize: function(options) {
    this.editor = options.editor;
    this.toolbarId = options.toolbarId;

    this.predicate = this.editor.options.property;
    this.editorName = this.editor.options.editorName;

    // Only start listening to events as soon as we're no longer in the 'inactive' state.
    this.undelegateEvents();
  },

  /**
   * Listens to editor state changes.
   */
  stateChange: function(from, to) {
    switch (to) {
      case 'inactive':
        if (from !== null) {
          this.undecorate();
          if (from === 'invalid') {
            this._removeValidationErrors();
          }
        }
        break;
      case 'candidate':
        this.decorate();
        if (from !== 'inactive') {
          this.stopHighlight();
          if (from !== 'highlighted') {
            this.stopEdit();
            if (from === 'invalid') {
              this._removeValidationErrors();
            }
          }
        }
        break;
      case 'highlighted':
        this.startHighlight();
        break;
      case 'activating':
        // NOTE: this state is not used by every editor! It's only used by those
        // that need to interact with the server.
        this.prepareEdit();
        break;
      case 'active':
        if (from !== 'activating') {
          this.prepareEdit();
        }
        this.startEdit();
        break;
      case 'changed':
        break;
      case 'saving':
        if (from === 'invalid') {
          this._removeValidationErrors();
        }
        break;
      case 'saved':
        break;
      case 'invalid':
        break;
    }
  },

  /**
   * Starts hover: transition to 'highlight' state.
   *
   * @param event
   */
  onMouseEnter: function(event) {
    var that = this;
    this._ignoreHoveringVia(event, '#' + this.toolbarId, function () {
      var editableEntity = that.editor.options.widget;
      editableEntity.setState('highlighted', that.predicate);
      event.stopPropagation();
    });
  },

  /**
   * Stops hover: back to 'candidate' state.
   *
   * @param event
   */
  onMouseLeave: function(event) {
    var that = this;
    this._ignoreHoveringVia(event, '#' + this.toolbarId, function () {
      var editableEntity = that.editor.options.widget;
      editableEntity.setState('candidate', that.predicate, { reason: 'mouseleave' });
      event.stopPropagation();
    });
  },

  /**
   * Clicks: transition to 'activating' stage.
   *
   * @param event
   */
  onClick: function(event) {
    var editableEntity = this.editor.options.widget;
    editableEntity.setState('activating', this.predicate);
    event.preventDefault();
    event.stopPropagation();
  },

  decorate: function () {
    this.$el.addClass('edit-animate-fast edit-candidate edit-editable');
    this.delegateEvents();
  },

  undecorate: function () {
    this.$el
      .removeClass('edit-candidate edit-editable edit-highlighted edit-editing');
    this.undelegateEvents();
  },

  startHighlight: function () {
    // Animations.
    var that = this;
    setTimeout(function() {
      that.$el.addClass('edit-highlighted');
    }, 0);
  },

  stopHighlight: function() {
    this.$el
      .removeClass('edit-highlighted');
  },

  prepareEdit: function() {
    this.$el.addClass('edit-editing');

    // While editing, don't show *any* other editors.
    // @todo: BLOCKED_ON(Create.js, https://github.com/bergie/create/issues/133)
    // Revisit this.
    $('.edit-candidate').not('.edit-editing').removeClass('edit-editable');
  },

  startEdit: function() {
    if (this.getEditUISetting('padding')) {
      this._pad();
    }
  },

  stopEdit: function() {
    this.$el.removeClass('edit-highlighted edit-editing');

    // Make the other editors show up again.
    // @todo: BLOCKED_ON(Create.js, https://github.com/bergie/create/issues/133)
    // Revisit this.
    $('.edit-candidate').addClass('edit-editable');

    if (this.getEditUISetting('padding')) {
      this._unpad();
    }
  },

  /**
   * Retrieves a setting of the editor-specific Edit UI integration.
   *
   * @see Drupal.edit.util.getEditUISetting().
   */
  getEditUISetting: function(setting) {
    return Drupal.edit.util.getEditUISetting(this.editor, setting);
  },

  _pad: function () {
    var self = this;

    // Add 5px padding for readability. This means we'll freeze the current
    // width and *then* add 5px padding, hence ensuring the padding is added "on
    // the outside".
    // 1) Freeze the width (if it's not already set); don't use animations.
    if (this.$el[0].style.width === "") {
      this._widthAttributeIsEmpty = true;
      this.$el
        .addClass('edit-animate-disable-width')
        .css('width', this.$el.width())
        .css('background-color', this._getBgColor(this.$el));
    }

    // 2) Add padding; use animations.
    var posProp = this._getPositionProperties(this.$el);
    setTimeout(function() {
      // Re-enable width animations (padding changes affect width too!).
      self.$el.removeClass('edit-animate-disable-width');

      // Pad the editable.
      self.$el
      .css({
        'position': 'relative',
        'top':  posProp.top  - 5 + 'px',
        'left': posProp.left - 5 + 'px',
        'padding-top'   : posProp['padding-top']    + 5 + 'px',
        'padding-left'  : posProp['padding-left']   + 5 + 'px',
        'padding-right' : posProp['padding-right']  + 5 + 'px',
        'padding-bottom': posProp['padding-bottom'] + 5 + 'px',
        'margin-bottom':  posProp['margin-bottom'] - 10 + 'px'
      });
    }, 0);
  },

  _unpad: function () {
    var self = this;

    // 1) Set the empty width again.
    if (this._widthAttributeIsEmpty) {
      this.$el
        .addClass('edit-animate-disable-width')
        .css('width', '')
        .css('background-color', '');
    }

    // 2) Remove padding; use animations (these will run simultaneously with)
    // the fading out of the toolbar as its gets removed).
    var posProp = this._getPositionProperties(this.$el);
    setTimeout(function() {
      // Re-enable width animations (padding changes affect width too!).
      self.$el.removeClass('edit-animate-disable-width');

      // Unpad the editable.
      self.$el
      .css({
        'position': 'relative',
        'top':  posProp.top  + 5 + 'px',
        'left': posProp.left + 5 + 'px',
        'padding-top'   : posProp['padding-top']    - 5 + 'px',
        'padding-left'  : posProp['padding-left']   - 5 + 'px',
        'padding-right' : posProp['padding-right']  - 5 + 'px',
        'padding-bottom': posProp['padding-bottom'] - 5 + 'px',
        'margin-bottom': posProp['margin-bottom'] + 10 + 'px'
      });
    }, 0);
  },

  /**
   * Gets the background color of an element (or the inherited one).
   *
   * @param $e
   *   A DOM element.
   */
  _getBgColor: function($e) {
    var c;

    if ($e === null || $e[0].nodeName === 'HTML') {
      // Fallback to white.
      return 'rgb(255, 255, 255)';
    }
    c = $e.css('background-color');
    // TRICKY: edge case for Firefox' "transparent" here; this is a
    // browser bug: https://bugzilla.mozilla.org/show_bug.cgi?id=635724
    if (c === 'rgba(0, 0, 0, 0)' || c === 'transparent') {
      return this._getBgColor($e.parent());
    }
    return c;
  },

  /**
   * Gets the top and left properties of an element and convert extraneous
   * values and information into numbers ready for subtraction.
   *
   * @param $e
   *   A DOM element.
   */
  _getPositionProperties: function($e) {
    var p,
        r = {},
        props = [
          'top', 'left', 'bottom', 'right',
          'padding-top', 'padding-left', 'padding-right', 'padding-bottom',
          'margin-bottom'
        ];

    var propCount = props.length;
    for (var i = 0; i < propCount; i++) {
      p = props[i];
      r[p] = parseInt(this._replaceBlankPosition($e.css(p)), 10);
    }
    return r;
  },

  /**
   * Replaces blank or 'auto' CSS "position: <value>" values with "0px".
   *
   * @param pos
   *   The value for a CSS position declaration.
   */
  _replaceBlankPosition: function(pos) {
    if (pos === 'auto' || !pos) {
      pos = '0px';
    }
    return pos;
  },

  /**
   * Ignores hovering to/from the given closest element, but as soon as a hover
   * occurs to/from *another* element, then call the given callback.
   */
  _ignoreHoveringVia: function(event, closest, callback) {
    if ($(event.relatedTarget).closest(closest).length > 0) {
      event.stopPropagation();
    }
    else {
      callback();
    }
  },

  /**
   * Removes validation errors' markup changes, if any.
   *
   * Note: this only needs to happen for type=direct, because for type=direct,
   * the property DOM element itself is modified; this is not the case for
   * type=form.
   */
  _removeValidationErrors: function() {
    if (this.editorName !== 'form') {
      this.$el
        .removeClass('edit-validation-error')
        .next('.edit-validation-errors')
        .remove();
    }
  }

});

})(jQuery, Backbone, Drupal);
