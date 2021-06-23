/**
 * @file
 * A Backbone View that decorates the in-place edited element.
 */

(function ($, Backbone, Drupal) {
  Drupal.quickedit.FieldDecorationView = Backbone.View.extend(
    /** @lends Drupal.quickedit.FieldDecorationView# */ {
      /**
       * @type {object}
       */
      events: {
        'mouseenter.quickedit': 'onMouseEnter',
        'mouseleave.quickedit': 'onMouseLeave',
        click: 'onClick',
        'tabIn.quickedit': 'onMouseEnter',
        'tabOut.quickedit': 'onMouseLeave',
      },

      /**
       * @constructs
       *
       * @augments Backbone.View
       *
       * @param {object} options
       *   An object with the following keys:
       * @param {Drupal.quickedit.EditorView} options.editorView
       *   The editor object view.
       */
      initialize(options) {
        this.editorView = options.editorView;

        this.listenTo(this.model, 'change:state', this.stateChange);
        this.listenTo(
          this.model,
          'change:isChanged change:inTempStore',
          this.renderChanged,
        );
      },

      /**
       * {@inheritdoc}
       */
      remove() {
        // The el property is the field, which should not be removed. Remove the
        // pointer to it, then call Backbone.View.prototype.remove().
        this.setElement();
        Backbone.View.prototype.remove.call(this);
      },

      /**
       * Determines the actions to take given a change of state.
       *
       * @param {Drupal.quickedit.FieldModel} model
       *   The `FieldModel` model.
       * @param {string} state
       *   The state of the associated field. One of
       *   {@link Drupal.quickedit.FieldModel.states}.
       */
      stateChange(model, state) {
        const from = model.previous('state');
        const to = state;
        switch (to) {
          case 'inactive':
            this.undecorate();
            break;

          case 'candidate':
            this.decorate();
            if (from !== 'inactive') {
              this.stopHighlight();
              if (from !== 'highlighted') {
                this.model.set('isChanged', false);
                this.stopEdit();
              }
            }
            this.$el.removeClass('quickedit-editing--padded');
            break;

          case 'highlighted':
            this.startHighlight();
            break;

          case 'activating':
            // NOTE: this state is not used by every editor! It's only used by
            // those that need to interact with the server.
            this.prepareEdit();
            break;

          case 'active':
            if (from !== 'activating') {
              this.prepareEdit();
            }
            if (this.editorView.getQuickEditUISettings().padding) {
              this.$el.addClass('quickedit-editing--padded');
            }
            break;

          case 'changed':
            this.model.set('isChanged', true);
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
       * Adds a class to the edited element that indicates whether the field has
       * been changed by the user (i.e. locally) or the field has already been
       * changed and stored before by the user (i.e. remotely, stored in
       * PrivateTempStore).
       */
      renderChanged() {
        this.$el.toggleClass(
          'quickedit-changed',
          this.model.get('isChanged') || this.model.get('inTempStore'),
        );
      },

      /**
       * Starts hover; transitions to 'highlight' state.
       *
       * @param {jQuery.Event} event
       *   The mouse event.
       */
      onMouseEnter(event) {
        const that = this;
        that.model.set('state', 'highlighted');
        event.stopPropagation();
      },

      /**
       * Stops hover; transitions to 'candidate' state.
       *
       * @param {jQuery.Event} event
       *   The mouse event.
       */
      onMouseLeave(event) {
        const that = this;
        that.model.set('state', 'candidate', { reason: 'mouseleave' });
        event.stopPropagation();
      },

      /**
       * Transition to 'activating' stage.
       *
       * @param {jQuery.Event} event
       *   The click event.
       */
      onClick(event) {
        this.model.set('state', 'activating');
        event.preventDefault();
        event.stopPropagation();
      },

      /**
       * Adds classes used to indicate an elements editable state.
       */
      decorate() {
        this.$el.addClass('quickedit-candidate quickedit-editable');
      },

      /**
       * Removes classes used to indicate an elements editable state.
       */
      undecorate() {
        this.$el.removeClass(
          'quickedit-candidate quickedit-editable quickedit-highlighted quickedit-editing',
        );
      },

      /**
       * Adds that class that indicates that an element is highlighted.
       */
      startHighlight() {
        // Animations.
        const that = this;
        // Use a timeout to grab the next available animation frame.
        that.$el.addClass('quickedit-highlighted');
      },

      /**
       * Removes the class that indicates that an element is highlighted.
       */
      stopHighlight() {
        this.$el.removeClass('quickedit-highlighted');
      },

      /**
       * Removes the class that indicates that an element as editable.
       */
      prepareEdit() {
        this.$el.addClass('quickedit-editing');

        // Allow the field to be styled differently while editing in a pop-up
        // in-place editor.
        if (this.editorView.getQuickEditUISettings().popup) {
          this.$el.addClass('quickedit-editor-is-popup');
        }
      },

      /**
       * Removes the class that indicates that an element is being edited.
       *
       * Reapplies the class that indicates that a candidate editable element is
       * again available to be edited.
       */
      stopEdit() {
        this.$el.removeClass('quickedit-highlighted quickedit-editing');

        // Done editing in a pop-up in-place editor; remove the class.
        if (this.editorView.getQuickEditUISettings().popup) {
          this.$el.removeClass('quickedit-editor-is-popup');
        }

        // Make the other editors show up again.
        $('.quickedit-candidate').addClass('quickedit-editable');
      },
    },
  );
})(jQuery, Backbone, Drupal);
