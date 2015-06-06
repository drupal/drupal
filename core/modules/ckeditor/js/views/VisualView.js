/**
 * @file
 * A Backbone View that provides the visual UX view of CKEditor toolbar
 *   configuration.
 */

(function (Drupal, Backbone, $) {

  "use strict";

  Drupal.ckeditor.VisualView = Backbone.View.extend(/** @lends Drupal.ckeditor.VisualView# */{

    events: {
      'click .ckeditor-toolbar-group-name': 'onGroupNameClick',
      'click .ckeditor-groupnames-toggle': 'onGroupNamesToggleClick',
      'click .ckeditor-add-new-group button': 'onAddGroupButtonClick'
    },

    /**
     * Backbone View for CKEditor toolbar configuration; visual UX.
     *
     * @constructs
     *
     * @augments Backbone.View
     */
    initialize: function () {
      this.listenTo(this.model, 'change:isDirty change:groupNamesVisible', this.render);

      // Add a toggle for the button group names.
      $(Drupal.theme('ckeditorButtonGroupNamesToggle'))
        .prependTo(this.$el.find('#ckeditor-active-toolbar').parent());

      this.render();
    },

    /**
     *
     * @param {*} model
     * @param {string} [value]
     * @param {object} changedAttributes
     *
     * @return {Drupal.ckeditor.VisualView}
     */
    render: function (model, value, changedAttributes) {
      this.insertPlaceholders();
      this.applySorting();

      // Toggle button group names.
      var groupNamesVisible = this.model.get('groupNamesVisible');
      // If a button was just placed in the active toolbar, ensure that the
      // button group names are visible.
      if (changedAttributes && changedAttributes.changes && changedAttributes.changes.isDirty) {
        this.model.set({groupNamesVisible: true}, {silent: true});
        groupNamesVisible = true;
      }
      this.$el.find('[data-toolbar="active"]').toggleClass('ckeditor-group-names-are-visible', groupNamesVisible);
      this.$el.find('.ckeditor-groupnames-toggle')
        .text((groupNamesVisible) ? Drupal.t('Hide group names') : Drupal.t('Show group names'))
        .attr('aria-pressed', groupNamesVisible);

      return this;
    },

    /**
     * Handles clicks to a button group name.
     *
     * @param {jQuery.Event} event
     */
    onGroupNameClick: function (event) {
      var $group = $(event.currentTarget).closest('.ckeditor-toolbar-group');
      Drupal.ckeditor.openGroupNameDialog(this, $group);

      event.stopPropagation();
      event.preventDefault();
    },

    /**
     * Handles clicks on the button group names toggle button.
     *
     * @param {jQuery.Event} event
     */
    onGroupNamesToggleClick: function (event) {
      this.model.set('groupNamesVisible', !this.model.get('groupNamesVisible'));
      event.preventDefault();
    },

    /**
     * Prompts the user to provide a name for a new button group; inserts it.
     *
     * @param {jQuery.Event} event
     */
    onAddGroupButtonClick: function (event) {

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
          $group.appendTo($(event.currentTarget).closest('.ckeditor-row').children('.ckeditor-toolbar-groups'));
          // Focus on the new group.
          $group.trigger('focus');
        }
      }

      // Pass in a DOM fragment of a placeholder group so that the new group
      // name can be applied to it.
      Drupal.ckeditor.openGroupNameDialog(this, $(Drupal.theme('ckeditorToolbarGroup')), insertNewGroup);

      event.preventDefault();
    },

    /**
     * Handles jQuery Sortable stop sort of a button group.
     *
     * @param {jQuery.Event} event
     * @param {object} ui
     *   A jQuery.ui.sortable argument that contains information about the
     *   elements involved in the sort action.
     */
    endGroupDrag: function (event, ui) {
      var view = this;
      Drupal.ckeditor.registerGroupMove(this, ui.item, function (success) {
        if (!success) {
          // Cancel any sorting in the configuration area.
          view.$el.find('.ckeditor-toolbar-configuration').find('.ui-sortable').sortable('cancel');
        }
      });
    },

    /**
     * Handles jQuery Sortable start sort of a button.
     *
     * @param {jQuery.Event} event
     * @param {object} ui
     *   A jQuery.ui.sortable argument that contains information about the
     *   elements involved in the sort action.
     */
    startButtonDrag: function (event, ui) {
      this.$el.find('a:focus').trigger('blur');

      // Show the button group names as soon as the user starts dragging.
      this.model.set('groupNamesVisible', true);
    },

    /**
     * Handles jQuery Sortable stop sort of a button.
     *
     * @param {jQuery.Event} event
     * @param {object} ui
     *   A jQuery.ui.sortable argument that contains information about the
     *   elements involved in the sort action.
     */
    endButtonDrag: function (event, ui) {
      var view = this;
      Drupal.ckeditor.registerButtonMove(this, ui.item, function (success) {
        if (!success) {
          // Cancel any sorting in the configuration area.
          view.$el.find('.ui-sortable').sortable('cancel');
        }
        // Refocus the target button so that the user can continue from a known
        // place.
        ui.item.find('a').trigger('focus');
      });
    },

    /**
     * Invokes jQuery.sortable() on new buttons and groups in a CKEditor config.
     */
    applySorting: function () {
      // Make the buttons sortable.
      this.$el.find('.ckeditor-buttons').not('.ui-sortable').sortable({
        // Change this to .ckeditor-toolbar-group-buttons.
        connectWith: '.ckeditor-buttons',
        placeholder: 'ckeditor-button-placeholder',
        forcePlaceholderSize: true,
        tolerance: 'pointer',
        cursor: 'move',
        start: this.startButtonDrag.bind(this),
        // Sorting within a sortable.
        stop: this.endButtonDrag.bind(this)
      }).disableSelection();

      // Add the drag and drop functionality to button groups.
      this.$el.find('.ckeditor-toolbar-groups').not('.ui-sortable').sortable({
        connectWith: '.ckeditor-toolbar-groups',
        cancel: '.ckeditor-add-new-group',
        placeholder: 'ckeditor-toolbar-group-placeholder',
        forcePlaceholderSize: true,
        cursor: 'move',
        stop: this.endGroupDrag.bind(this)
      });

      // Add the drag and drop functionality to buttons.
      this.$el.find('.ckeditor-multiple-buttons li').draggable({
        connectToSortable: '.ckeditor-toolbar-active .ckeditor-buttons',
        helper: 'clone'
      });
    },

    /**
     * Wraps the invocation of methods to insert blank groups and rows.
     */
    insertPlaceholders: function () {
      this.insertPlaceholderRow();
      this.insertNewGroupButtons();
    },

    /**
     * Inserts a blank row at the bottom of the CKEditor configuration.
     */
    insertPlaceholderRow: function () {
      var $rows = this.$el.find('.ckeditor-row');
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
      var len = $rows.length;
      $rows.filter(function (index, row) {
        // Do not remove the last row.
        if (index + 1 === len) {
          return false;
        }
        return $(row).find('.ckeditor-toolbar-group').not('.placeholder').length === 0;
      })
        // Then get all rows that are placeholders and remove them.
        .remove();
    },

    /**
     * Inserts a button in each row that will add a new CKEditor button group.
     */
    insertNewGroupButtons: function () {
      // Insert an add group button to each row.
      this.$el.find('.ckeditor-row').each(function () {
        var $row = $(this);
        var $groups = $row.find('.ckeditor-toolbar-group');
        var $button = $row.find('.ckeditor-add-new-group');
        if ($button.length === 0) {
          $row.children('.ckeditor-toolbar-groups').append(Drupal.theme('ckeditorNewButtonGroup'));
        }
        // If a placeholder group exists, make sure it's at the end of the row.
        else if (!$groups.eq(-1).hasClass('ckeditor-add-new-group')) {
          $button.appendTo($row.children('.ckeditor-toolbar-groups'));
        }
      });
    }
  });

})(Drupal, Backbone, jQuery);
