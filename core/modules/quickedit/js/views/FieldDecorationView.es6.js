/**
 * @file
 * A Backbone View that decorates the in-place edited element.
 */

(function ($, Backbone, Drupal) {
  Drupal.quickedit.FieldDecorationView = Backbone.View.extend(
    /** @lends Drupal.quickedit.FieldDecorationView# */ {
      /**
       * @type {null}
       */
      _widthAttributeIsEmpty: null,

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
            this._unpad();
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
              this._pad();
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

      /**
       * Adds padding around the editable element to make it pop visually.
       */
      _pad() {
        // Early return if the element has already been padded.
        if (this.$el.data('quickedit-padded')) {
          return;
        }
        const self = this;

        // Add 5px padding for readability. This means we'll freeze the current
        // width and *then* add 5px padding, hence ensuring the padding is added
        // "on the outside".
        // 1) Freeze the width (if it's not already set); don't use animations.
        if (this.$el[0].style.width === '') {
          this._widthAttributeIsEmpty = true;
          this.$el
            .addClass('quickedit-animate-disable-width')
            .css('width', this.$el.width());
        }

        // 2) Add padding; use animations.
        const posProp = this._getPositionProperties(this.$el);
        setTimeout(() => {
          // Re-enable width animations (padding changes affect width too!).
          self.$el.removeClass('quickedit-animate-disable-width');

          // Pad the editable.
          self.$el
            .css({
              position: 'relative',
              top: `${posProp.top - 5}px`,
              left: `${posProp.left - 5}px`,
              'padding-top': `${posProp['padding-top'] + 5}px`,
              'padding-left': `${posProp['padding-left'] + 5}px`,
              'padding-right': `${posProp['padding-right'] + 5}px`,
              'padding-bottom': `${posProp['padding-bottom'] + 5}px`,
              'margin-bottom': `${posProp['margin-bottom'] - 10}px`,
            })
            .data('quickedit-padded', true);
        }, 0);
      },

      /**
       * Removes the padding around the element being edited when editing ceases.
       */
      _unpad() {
        // Early return if the element has not been padded.
        if (!this.$el.data('quickedit-padded')) {
          return;
        }
        const self = this;

        // 1) Set the empty width again.
        if (this._widthAttributeIsEmpty) {
          this.$el.addClass('quickedit-animate-disable-width').css('width', '');
        }

        // 2) Remove padding; use animations (these will run simultaneously with)
        // the fading out of the toolbar as its gets removed).
        const posProp = this._getPositionProperties(this.$el);
        setTimeout(() => {
          // Re-enable width animations (padding changes affect width too!).
          self.$el.removeClass('quickedit-animate-disable-width');

          // Unpad the editable.
          self.$el.css({
            position: 'relative',
            top: `${posProp.top + 5}px`,
            left: `${posProp.left + 5}px`,
            'padding-top': `${posProp['padding-top'] - 5}px`,
            'padding-left': `${posProp['padding-left'] - 5}px`,
            'padding-right': `${posProp['padding-right'] - 5}px`,
            'padding-bottom': `${posProp['padding-bottom'] - 5}px`,
            'margin-bottom': `${posProp['margin-bottom'] + 10}px`,
          });
        }, 0);
        // Remove the marker that indicates that this field has padding. This is
        // done outside the timed out function above so that we don't get numerous
        // queued functions that will remove padding before the data marker has
        // been removed.
        this.$el.removeData('quickedit-padded');
      },

      /**
       * Gets the top and left properties of an element.
       *
       * Convert extraneous values and information into numbers ready for
       * subtraction.
       *
       * @param {jQuery} $e
       *   The element to get position properties from.
       *
       * @return {object}
       *   An object containing css values for the needed properties.
       */
      _getPositionProperties($e) {
        let p;
        const r = {};
        const props = [
          'top',
          'left',
          'bottom',
          'right',
          'padding-top',
          'padding-left',
          'padding-right',
          'padding-bottom',
          'margin-bottom',
        ];

        const propCount = props.length;
        for (let i = 0; i < propCount; i++) {
          p = props[i];
          r[p] = parseInt(this._replaceBlankPosition($e.css(p)), 10);
        }
        return r;
      },

      /**
       * Replaces blank or 'auto' CSS `position: <value>` values with "0px".
       *
       * @param {string} [pos]
       *   The value for a CSS position declaration.
       *
       * @return {string}
       *   A CSS value that is valid for `position`.
       */
      _replaceBlankPosition(pos) {
        if (pos === 'auto' || !pos) {
          pos = '0px';
        }
        return pos;
      },
    },
  );
})(jQuery, Backbone, Drupal);
