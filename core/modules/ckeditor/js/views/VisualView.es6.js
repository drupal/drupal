/**
 * @file
 * A Backbone View that provides the visual UX view of CKEditor toolbar
 *   configuration.
 */

(function (Drupal, Backbone, $, Sortable) {
  Drupal.ckeditor.VisualView = Backbone.View.extend(
    /** @lends Drupal.ckeditor.VisualView# */ {
      events: {
        'click .ckeditor-toolbar-group-name': 'onGroupNameClick',
        'click .ckeditor-groupnames-toggle': 'onGroupNamesToggleClick',
        'click .ckeditor-add-new-group button': 'onAddGroupButtonClick',
      },

      /**
       * Backbone View for CKEditor toolbar configuration; visual UX.
       *
       * @constructs
       *
       * @augments Backbone.View
       */
      initialize() {
        this.listenTo(
          this.model,
          'change:isDirty change:groupNamesVisible',
          this.render,
        );

        // Add a toggle for the button group names.
        $(Drupal.theme('ckeditorButtonGroupNamesToggle')).prependTo(
          this.$el.find('#ckeditor-active-toolbar').parent(),
        );

        this.render();
      },

      /**
       * Render function for rendering the toolbar configuration.
       *
       * @param {*} model
       *   Model used for the view.
       * @param {string} [value]
       *   The value that was changed.
       * @param {object} changedAttributes
       *   The attributes that was changed.
       *
       * @return {Drupal.ckeditor.VisualView}
       *   The {@link Drupal.ckeditor.VisualView} object.
       */
      render(model, value, changedAttributes) {
        this.insertPlaceholders();
        this.applySorting();

        // Toggle button group names.
        let groupNamesVisible = this.model.get('groupNamesVisible');
        // If a button was just placed in the active toolbar, ensure that the
        // button group names are visible.
        if (
          changedAttributes &&
          changedAttributes.changes &&
          changedAttributes.changes.isDirty
        ) {
          this.model.set({ groupNamesVisible: true }, { silent: true });
          groupNamesVisible = true;
        }
        this.$el
          .find('[data-toolbar="active"]')
          .toggleClass('ckeditor-group-names-are-visible', groupNamesVisible);
        const $toggle = this.$el.find('.ckeditor-groupnames-toggle');
        $toggle
          .each((index, element) => {
            element.textContent = groupNamesVisible
              ? Drupal.t('Hide group names')
              : Drupal.t('Show group names');
          })
          .attr('aria-pressed', groupNamesVisible);
        return this;
      },

      /**
       * Handles clicks to a button group name.
       *
       * @param {jQuery.Event} event
       *   The click event on the button group.
       */
      onGroupNameClick(event) {
        const $group = $(event.currentTarget).closest(
          '.ckeditor-toolbar-group',
        );
        Drupal.ckeditor.openGroupNameDialog(this, $group);

        event.stopPropagation();
        event.preventDefault();
      },

      /**
       * Handles clicks on the button group names toggle button.
       *
       * @param {jQuery.Event} event
       *   The click event on the toggle button.
       */
      onGroupNamesToggleClick(event) {
        this.model.set(
          'groupNamesVisible',
          !this.model.get('groupNamesVisible'),
        );
        event.preventDefault();
      },

      /**
       * Prompts the user to provide a name for a new button group; inserts it.
       *
       * @param {jQuery.Event} event
       *   The event of the button click.
       */
      onAddGroupButtonClick(event) {
        /**
         * Inserts a new button if the openGroupNameDialog function returns true.
         *
         * @param {bool} success
         *   A flag that indicates if the user created a new group (true) or
         *   canceled out of the dialog (false).
         * @param {jQuery} $group
         *   A jQuery DOM fragment that represents the new button group. It has
         *   not been added to the DOM yet.
         */
        function insertNewGroup(success, $group) {
          if (success) {
            $group.appendTo(
              $(event.currentTarget)
                .closest('.ckeditor-row')
                .children('.ckeditor-toolbar-groups'),
            );
            // Focus on the new group.
            $group.trigger('focus');
          }
        }

        // Pass in a DOM fragment of a placeholder group so that the new group
        // name can be applied to it.
        Drupal.ckeditor.openGroupNameDialog(
          this,
          $(Drupal.theme('ckeditorToolbarGroup')),
          insertNewGroup,
        );

        event.preventDefault();
      },

      /**
       * Handles Sortable stop sort of a button group.
       *
       * @param {CustomEvent} event
       *   The event triggered on the group drag.
       */
      endGroupDrag(event) {
        const $item = $(event.item);
        Drupal.ckeditor.registerGroupMove(this, $item);
      },

      /**
       * Handles Sortable start sort of a button.
       *
       * @param {CustomEvent} event
       *   The event triggered on the button drag.
       */
      startButtonDrag(event) {
        this.$el.find('a:focus').trigger('blur');

        // Show the button group names as soon as the user starts dragging.
        this.model.set('groupNamesVisible', true);
      },

      /**
       * Handles Sortable stop sort of a button.
       *
       * @param {CustomEvent} event
       *   The event triggered on the button drag.
       */
      endButtonDrag(event) {
        const $item = $(event.item);

        Drupal.ckeditor.registerButtonMove(this, $item, (success) => {
          // Refocus the target button so that the user can continue
          // from a known place.
          $item.find('a').trigger('focus');
        });
      },

      /**
       * Invokes Sortable() on new buttons and groups in a CKEditor config.
       * Array.prototype.forEach is used here because of the lack of support for
       * NodeList.forEach in older browsers.
       */
      applySorting() {
        // Make the buttons sortable.
        Array.prototype.forEach.call(
          this.el.querySelectorAll('.ckeditor-buttons:not(.js-sortable)'),
          (buttons) => {
            buttons.classList.add('js-sortable');
            Sortable.create(buttons, {
              ghostClass: 'ckeditor-button-placeholder',
              group: 'ckeditor-buttons',
              onStart: this.startButtonDrag.bind(this),
              onEnd: this.endButtonDrag.bind(this),
            });
          },
        );

        Array.prototype.forEach.call(
          this.el.querySelectorAll(
            '.ckeditor-toolbar-groups:not(.js-sortable)',
          ),
          (buttons) => {
            buttons.classList.add('js-sortable');
            Sortable.create(buttons, {
              ghostClass: 'ckeditor-toolbar-group-placeholder',
              onEnd: this.endGroupDrag.bind(this),
            });
          },
        );

        Array.prototype.forEach.call(
          this.el.querySelectorAll(
            '.ckeditor-multiple-buttons:not(.js-sortable)',
          ),
          (buttons) => {
            buttons.classList.add('js-sortable');
            Sortable.create(buttons, {
              group: {
                name: 'ckeditor-buttons',
                pull: 'clone',
              },
              onEnd: this.endButtonDrag.bind(this),
            });
          },
        );
      },

      /**
       * Wraps the invocation of methods to insert blank groups and rows.
       */
      insertPlaceholders() {
        this.insertPlaceholderRow();
        this.insertNewGroupButtons();
      },

      /**
       * Inserts a blank row at the bottom of the CKEditor configuration.
       */
      insertPlaceholderRow() {
        let $rows = this.$el.find('.ckeditor-row');
        // Add a placeholder row. to the end of the list if one does not exist.
        if (!$rows.eq(-1).hasClass('placeholder')) {
          this.$el
            .find('.ckeditor-toolbar-active')
            .children('.ckeditor-active-toolbar-configuration')
            .append(Drupal.theme('ckeditorRow'));
        }
        // Update the $rows variable to include the new row.
        $rows = this.$el.find('.ckeditor-row');
        // Remove blank rows except the last one.
        const len = $rows.length;
        $rows
          .filter((index, row) => {
            // Do not remove the last row.
            if (index + 1 === len) {
              return false;
            }
            return (
              $(row).find('.ckeditor-toolbar-group').not('.placeholder')
                .length === 0
            );
          })
          // Then get all rows that are placeholders and remove them.
          .remove();
      },

      /**
       * Inserts a button in each row that will add a new CKEditor button group.
       */
      insertNewGroupButtons() {
        // Insert an add group button to each row.
        this.$el.find('.ckeditor-row').each(function () {
          const $row = $(this);
          const $groups = $row.find('.ckeditor-toolbar-group');
          const $button = $row.find('.ckeditor-add-new-group');
          if ($button.length === 0) {
            $row
              .children('.ckeditor-toolbar-groups')
              .append(Drupal.theme('ckeditorNewButtonGroup'));
          }
          // If a placeholder group exists, make sure it's at the end of the row.
          else if (!$groups.eq(-1).hasClass('ckeditor-add-new-group')) {
            $button.appendTo($row.children('.ckeditor-toolbar-groups'));
          }
        });
      },
    },
  );
})(Drupal, Backbone, jQuery, Sortable);
