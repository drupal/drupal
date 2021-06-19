/**
 * @file
 * A Backbone View that provides an entity level toolbar.
 */

(function ($, _, Backbone, Drupal, debounce, Popper) {
  Drupal.quickedit.EntityToolbarView = Backbone.View.extend(
    /** @lends Drupal.quickedit.EntityToolbarView# */ {
      /**
       * @type {jQuery}
       */
      _fieldToolbarRoot: null,

      /**
       * @return {object}
       *   A map of events.
       */
      events() {
        const map = {
          'click button.action-save': 'onClickSave',
          'click button.action-cancel': 'onClickCancel',
          mouseenter: 'onMouseenter',
        };
        return map;
      },

      /**
       * @constructs
       *
       * @augments Backbone.View
       *
       * @param {object} options
       *   Options to construct the view.
       * @param {Drupal.quickedit.AppModel} options.appModel
       *   A quickedit `AppModel` to use in the view.
       */
      initialize(options) {
        const that = this;
        this.appModel = options.appModel;
        this.$entity = $(this.model.get('el'));

        // Rerender whenever the entity state changes.
        this.listenTo(
          this.model,
          'change:isActive change:isDirty change:state',
          this.render,
        );
        // Also rerender whenever a different field is highlighted or activated.
        this.listenTo(
          this.appModel,
          'change:highlightedField change:activeField',
          this.render,
        );
        // Rerender when a field of the entity changes state.
        this.listenTo(
          this.model.get('fields'),
          'change:state',
          this.fieldStateChange,
        );

        // Reposition the entity toolbar as the viewport and the position within
        // the viewport changes.
        $(window).on(
          'resize.quickedit scroll.quickedit drupalViewportOffsetChange.quickedit',
          debounce($.proxy(this.windowChangeHandler, this), 150),
        );

        // Adjust the fence placement within which the entity toolbar may be
        // positioned.
        $(document).on(
          'drupalViewportOffsetChange.quickedit',
          (event, offsets) => {
            if (that.$fence) {
              that.$fence.css(offsets);
            }
          },
        );

        // Set the entity toolbar DOM element as the el for this view.
        const $toolbar = this.buildToolbarEl();
        this.setElement($toolbar);
        this._fieldToolbarRoot = $toolbar
          .find('.quickedit-toolbar-field')
          .get(0);

        // Initial render.
        this.render();
      },

      /**
       * {@inheritdoc}
       *
       * @return {Drupal.quickedit.EntityToolbarView}
       *   The entity toolbar view.
       */
      render() {
        if (this.model.get('isActive')) {
          // If the toolbar container doesn't exist, create it.
          const $body = $('body');
          if ($body.children('#quickedit-entity-toolbar').length === 0) {
            $body.append(this.$el);
          }
          // The fence will define an area on the screen that the entity toolbar
          // will be positioned within.
          if ($body.children('#quickedit-toolbar-fence').length === 0) {
            this.$fence = $(Drupal.theme('quickeditEntityToolbarFence'))
              .css(Drupal.displace())
              .appendTo($body);
          }
          // Adds the entity title to the toolbar.
          this.label();

          // Show the save and cancel buttons.
          this.show('ops');
          // If render is being called and the toolbar is already visible, just
          // reposition it.
          this.position();
        }

        // The save button text and state varies with the state of the entity
        // model.
        const $button = this.$el.find('.quickedit-button.action-save');
        const isDirty = this.model.get('isDirty');
        // Adjust the save button according to the state of the model.
        switch (this.model.get('state')) {
          // Quick editing is active, but no field is being edited.
          case 'opened':
            // The saving throbber is not managed by AJAX system. The
            // EntityToolbarView manages this visual element.
            $button
              .removeClass('action-saving icon-throbber icon-end')
              .text(Drupal.t('Save'))
              .removeAttr('disabled')
              .attr('aria-hidden', !isDirty);
            break;

          // The changes to the fields of the entity are being committed.
          case 'committing':
            $button
              .addClass('action-saving icon-throbber icon-end')
              .text(Drupal.t('Saving'))
              .attr('disabled', 'disabled');
            break;

          default:
            $button.attr('aria-hidden', true);
            break;
        }

        return this;
      },

      /**
       * {@inheritdoc}
       */
      remove() {
        // Remove additional DOM elements controlled by this View.
        this.$fence.remove();

        // Stop listening to additional events.
        $(window).off(
          'resize.quickedit scroll.quickedit drupalViewportOffsetChange.quickedit',
        );
        $(document).off('drupalViewportOffsetChange.quickedit');

        Backbone.View.prototype.remove.call(this);
      },

      /**
       * Repositions the entity toolbar on window scroll and resize.
       *
       * @param {jQuery.Event} event
       *   The scroll or resize event.
       */
      windowChangeHandler(event) {
        this.position();
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
      fieldStateChange(model, state) {
        switch (state) {
          case 'active':
            this.render();
            break;

          case 'invalid':
            this.render();
            break;
        }
      },

      /**
       * Uses the Popper() method to position the entity toolbar.
       *
       * @param {HTMLElement} [element]
       *   The element against which the entity toolbar is positioned.
       */
      position(element) {
        clearTimeout(this.timer);

        const that = this;
        // Vary the edge of the positioning according to the direction of language
        // in the document.
        const edge = document.documentElement.dir === 'rtl' ? 'right' : 'left';
        // A time unit to wait until the entity toolbar is repositioned.
        let delay = 0;
        // Determines what check in the series of checks below should be
        // evaluated.
        let check = 0;
        // When positioned against an active field that has padding, we should
        // ignore that padding when positioning the toolbar, to not unnecessarily
        // move the toolbar horizontally, which feels annoying.
        let horizontalPadding = 0;
        let of;
        let activeField;
        let highlightedField;
        // There are several elements in the page that the entity toolbar might be
        // positioned against. They are considered below in a priority order.
        do {
          switch (check) {
            case 0:
              // Position against a specific element.
              of = element;
              break;

            case 1:
              // Position against a form container.
              activeField = Drupal.quickedit.app.model.get('activeField');
              of =
                activeField &&
                activeField.editorView &&
                activeField.editorView.$formContainer &&
                activeField.editorView.$formContainer.find('.quickedit-form');
              break;

            case 2:
              // Position against an active field.
              of =
                activeField &&
                activeField.editorView &&
                activeField.editorView.getEditedElement();
              if (
                activeField &&
                activeField.editorView &&
                activeField.editorView.getQuickEditUISettings().padding
              ) {
                horizontalPadding = 5;
              }
              break;

            case 3:
              // Position against a highlighted field.
              highlightedField =
                Drupal.quickedit.app.model.get('highlightedField');
              of =
                highlightedField &&
                highlightedField.editorView &&
                highlightedField.editorView.getEditedElement();
              delay = 250;
              break;

            default: {
              const fieldModels = this.model.get('fields').models;
              let topMostPosition = 1000000;
              let topMostField = null;
              // Position against the topmost field.
              for (let i = 0; i < fieldModels.length; i++) {
                const pos = fieldModels[i]
                  .get('el')
                  .getBoundingClientRect().top;
                if (pos < topMostPosition) {
                  topMostPosition = pos;
                  topMostField = fieldModels[i];
                }
              }
              of = topMostField.get('el');
              delay = 50;
              break;
            }
          }
          // Prepare to check the next possible element to position against.
          check++;
        } while (!of);

        /**
         * Refines popper positioning.
         *
         * @param {object} data
         *   Data object containing popper and target data.
         */
        function refinePopper({ state }) {
          // Determine if the pointer should be on the top or bottom.
          const isBelow = state.placement.split('-')[0] === 'bottom';
          const classListMethod = isBelow ? 'add' : 'remove';
          state.elements.popper.classList[classListMethod](
            'quickedit-toolbar-pointer-top',
          );
        }
        /**
         * Calls the Popper() method on the $el of this view.
         */
        function positionToolbar() {
          const popperElement = that.el;
          const referenceElement = of;
          const boundariesElement = that.$fence[0];
          const popperedge = edge === 'left' ? 'start' : 'end';
          if (referenceElement !== undefined) {
            if (!popperElement.classList.contains('js-popper-processed')) {
              that.popper = Popper.createPopper(
                referenceElement,
                popperElement,
                {
                  placement: `top-${popperedge}`,
                  modifiers: [
                    {
                      name: 'flip',
                      options: {
                        boundary: boundariesElement,
                      },
                    },
                    {
                      name: 'preventOverflow',
                      options: {
                        boundary: boundariesElement,
                        tether: false,
                        altAxis: true,
                        padding: { top: 5, bottom: 5 },
                      },
                    },
                    {
                      name: 'computeStyles',
                      options: {
                        adaptive: false,
                      },
                    },
                    {
                      name: 'refinePopper',
                      phase: 'write',
                      enabled: true,
                      fn: refinePopper,
                    },
                  ],
                },
              );
              popperElement.classList.add('js-popper-processed');
            } else {
              that.popper.state.elements.reference = referenceElement[0]
                ? referenceElement[0]
                : referenceElement;
              that.popper.forceUpdate();
            }
          }

          that.$el
            // Resize the toolbar to match the dimensions of the field, up to a
            // maximum width that is equal to 90% of the field's width.
            .css({
              'max-width':
                document.documentElement.clientWidth < 450
                  ? document.documentElement.clientWidth
                  : 450,
              // Set a minimum width of 240px for the entity toolbar, or the width
              // of the client if it is less than 240px, so that the toolbar
              // never folds up into a squashed and jumbled mess.
              'min-width':
                document.documentElement.clientWidth < 240
                  ? document.documentElement.clientWidth
                  : 240,
              width: '100%',
            });
        }

        // Uses the jQuery.ui.position() method. Use a timeout to move the toolbar
        // only after the user has focused on an editable for 250ms. This prevents
        // the toolbar from jumping around the screen.
        this.timer = setTimeout(() => {
          // Render the position in the next execution cycle, so that animations
          // on the field have time to process. This is not strictly speaking, a
          // guarantee that all animations will be finished, but it's a simple
          // way to get better positioning without too much additional code.
          _.defer(positionToolbar);
        }, delay);
      },

      /**
       * Set the model state to 'saving' when the save button is clicked.
       *
       * @param {jQuery.Event} event
       *   The click event.
       */
      onClickSave(event) {
        event.stopPropagation();
        event.preventDefault();
        // Save the model.
        this.model.set('state', 'committing');
      },

      /**
       * Sets the model state to candidate when the cancel button is clicked.
       *
       * @param {jQuery.Event} event
       *   The click event.
       */
      onClickCancel(event) {
        event.preventDefault();
        this.model.set('state', 'deactivating');
      },

      /**
       * Clears the timeout that will eventually reposition the entity toolbar.
       *
       * Without this, it may reposition itself, away from the user's cursor!
       *
       * @param {jQuery.Event} event
       *   The mouse event.
       */
      onMouseenter(event) {
        clearTimeout(this.timer);
      },

      /**
       * Builds the entity toolbar HTML; attaches to DOM; sets starting position.
       *
       * @return {jQuery}
       *   The toolbar element.
       */
      buildToolbarEl() {
        const $toolbar = $(
          Drupal.theme('quickeditEntityToolbar', {
            id: 'quickedit-entity-toolbar',
          }),
        );

        $toolbar
          .find('.quickedit-toolbar-entity')
          // Append the "ops" toolgroup into the toolbar.
          .prepend(
            Drupal.theme('quickeditToolgroup', {
              classes: ['ops'],
              buttons: [
                {
                  label: Drupal.t('Save'),
                  type: 'submit',
                  classes: 'action-save quickedit-button icon',
                  attributes: {
                    'aria-hidden': true,
                  },
                },
                {
                  label: Drupal.t('Close'),
                  classes:
                    'action-cancel quickedit-button icon icon-close icon-only',
                },
              ],
            }),
          );

        // Give the toolbar a sensible starting position so that it doesn't
        // animate on to the screen from a far off corner.
        $toolbar.css({
          left: this.$entity.offset().left,
          top: this.$entity.offset().top,
        });

        return $toolbar;
      },

      /**
       * Returns the DOM element that fields will attach their toolbars to.
       *
       * @return {jQuery}
       *   The DOM element that fields will attach their toolbars to.
       */
      getToolbarRoot() {
        return this._fieldToolbarRoot;
      },

      /**
       * Generates a state-dependent label for the entity toolbar.
       */
      label() {
        // The entity label.
        let label = '';
        const entityLabel = this.model.get('label');

        // Label of an active field, if it exists.
        const activeField = Drupal.quickedit.app.model.get('activeField');
        const activeFieldLabel =
          activeField && activeField.get('metadata').label;
        // Label of a highlighted field, if it exists.
        const highlightedField =
          Drupal.quickedit.app.model.get('highlightedField');
        const highlightedFieldLabel =
          highlightedField && highlightedField.get('metadata').label;
        // The label is constructed in a priority order.
        if (activeFieldLabel) {
          label = Drupal.theme('quickeditEntityToolbarLabel', {
            entityLabel,
            fieldLabel: activeFieldLabel,
          });
        } else if (highlightedFieldLabel) {
          label = Drupal.theme('quickeditEntityToolbarLabel', {
            entityLabel,
            fieldLabel: highlightedFieldLabel,
          });
        } else {
          // @todo Add XSS regression test coverage in https://www.drupal.org/node/2547437
          label = Drupal.checkPlain(entityLabel);
        }

        this.$el.find('.quickedit-toolbar-label').html(label);
      },

      /**
       * Adds classes to a toolgroup.
       *
       * @param {string} toolgroup
       *   A toolgroup name.
       * @param {string} classes
       *   A string of space-delimited class names that will be applied to the
       *   wrapping element of the toolbar group.
       */
      addClass(toolgroup, classes) {
        this._find(toolgroup).addClass(classes);
      },

      /**
       * Removes classes from a toolgroup.
       *
       * @param {string} toolgroup
       *   A toolgroup name.
       * @param {string} classes
       *   A string of space-delimited class names that will be removed from the
       *   wrapping element of the toolbar group.
       */
      removeClass(toolgroup, classes) {
        this._find(toolgroup).removeClass(classes);
      },

      /**
       * Finds a toolgroup.
       *
       * @param {string} toolgroup
       *   A toolgroup name.
       *
       * @return {jQuery}
       *   The toolgroup DOM element.
       */
      _find(toolgroup) {
        return this.$el.find(
          `.quickedit-toolbar .quickedit-toolgroup.${toolgroup}`,
        );
      },

      /**
       * Shows a toolgroup.
       *
       * @param {string} toolgroup
       *   A toolgroup name.
       */
      show(toolgroup) {
        this.$el.removeClass('quickedit-animate-invisible');
      },
    },
  );
})(jQuery, _, Backbone, Drupal, Drupal.debounce, Popper);
