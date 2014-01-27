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

    _id: null,

    /**
     * {@inheritdoc}
     */
    initialize: function (options) {
      this.$editedElement = options.$editedElement;
      this.editorView = options.editorView;
      this.$root = this.$el;

      // Generate a DOM-compatible ID for the form container DOM element.
      this._id = 'edit-toolbar-for-' + this.model.id.replace(/[\/\[\]]/g, '_');

      this.listenTo(this.model, 'change:state', this.stateChange);
    },

    /**
     * {@inheritdoc}
     */
    render: function () {
      // Render toolbar and set it as the view's element.
      this.setElement($(Drupal.theme('editFieldToolbar', {
        id: this._id
      })));

      // Attach to the field toolbar $root element in the entity toolbar.
      this.$el.prependTo(this.$root);

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
          break;
        case 'candidate':
          // Remove the view's existing element if we went to the 'activating'
          // state or later, because it will be recreated. Not doing this would
          // result in memory leaks.
          if (from !== 'inactive' && from !== 'highlighted') {
            this.$el.remove();
            this.setElement();
          }
          break;
        case 'highlighted':
          break;
        case 'activating':
          this.render();

          if (this.editorView.getEditUISettings().fullWidthToolbar) {
            this.$el.addClass('edit-toolbar-fullwidth');
          }

          if (this.editorView.getEditUISettings().unifiedToolbar) {
            this.insertWYSIWYGToolGroups();
          }
          break;
        case 'active':
          break;
        case 'changed':
          break;
        case 'saving':
          break;
        case 'saved':
          break;
        case 'invalid':
          break;
      }
    },

    /**
     * Insert WYSIWYG markup into the associated toolbar.
     */
    insertWYSIWYGToolGroups: function () {
      this.$el
        .append(Drupal.theme('editToolgroup', {
          id: this.getFloatedWysiwygToolgroupId(),
          classes: ['wysiwyg-floated', 'edit-animate-slow', 'edit-animate-invisible', 'edit-animate-delay-veryfast'],
          buttons: []
        }))
        .append(Drupal.theme('editToolgroup', {
          id: this.getMainWysiwygToolgroupId(),
          classes: ['wysiwyg-main', 'edit-animate-slow', 'edit-animate-invisible', 'edit-animate-delay-veryfast'],
          buttons: []
        }));

      // Animate the toolgroups into visibility.
      this.show('wysiwyg-floated');
      this.show('wysiwyg-main');
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
     * Finds a toolgroup.
     *
     * @param String toolgroup
     *   A toolgroup name.
     * @return jQuery
     */
    _find: function (toolgroup) {
      return this.$el.find('.edit-toolgroup.' + toolgroup);
    },

    /**
     * Shows a toolgroup.
     *
     * @param String toolgroup
     *   A toolgroup name.
     */
    show: function (toolgroup) {
      var $group = this._find(toolgroup);
      // Attach a transitionEnd event handler to the toolbar group so that update
      // events can be triggered after the animations have ended.
      $group.on(Drupal.edit.util.constants.transitionEnd, function (event) {
        $group.off(Drupal.edit.util.constants.transitionEnd);
      });
      // The call to remove the class and start the animation must be started in
      // the next animation frame or the event handler attached above won't be
      // triggered.
      window.setTimeout(function () {
        $group.removeClass('edit-animate-invisible');
      }, 0);
    }

  });

})(jQuery, _, Backbone, Drupal);
