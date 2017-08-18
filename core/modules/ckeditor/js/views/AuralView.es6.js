/**
 * @file
 * A Backbone View that provides the aural view of CKEditor toolbar
 * configuration.
 */

(function (Drupal, Backbone, $) {
  Drupal.ckeditor.AuralView = Backbone.View.extend(/** @lends Drupal.ckeditor.AuralView# */{

    /**
     * @type {object}
     */
    events: {
      'click .ckeditor-buttons a': 'announceButtonHelp',
      'click .ckeditor-multiple-buttons a': 'announceSeparatorHelp',
      'focus .ckeditor-button a': 'onFocus',
      'focus .ckeditor-button-separator a': 'onFocus',
      'focus .ckeditor-toolbar-group': 'onFocus',
    },

    /**
     * Backbone View for CKEditor toolbar configuration; aural UX (output only).
     *
     * @constructs
     *
     * @augments Backbone.View
     */
    initialize() {
      // Announce the button and group positions when the model is no longer
      // dirty.
      this.listenTo(this.model, 'change:isDirty', this.announceMove);
    },

    /**
     * Calls announce on buttons and groups when their position is changed.
     *
     * @param {Drupal.ckeditor.ConfigurationModel} model
     *   The ckeditor configuration model.
     * @param {bool} isDirty
     *   A model attribute that indicates if the changed toolbar configuration
     *   has been stored or not.
     */
    announceMove(model, isDirty) {
      // Announce the position of a button or group after the model has been
      // updated.
      if (!isDirty) {
        const item = document.activeElement || null;
        if (item) {
          const $item = $(item);
          if ($item.hasClass('ckeditor-toolbar-group')) {
            this.announceButtonGroupPosition($item);
          }
          else if ($item.parent().hasClass('ckeditor-button')) {
            this.announceButtonPosition($item.parent());
          }
        }
      }
    },

    /**
     * Handles the focus event of elements in the active and available toolbars.
     *
     * @param {jQuery.Event} event
     *   The focus event that was triggered.
     */
    onFocus(event) {
      event.stopPropagation();

      const $originalTarget = $(event.target);
      const $currentTarget = $(event.currentTarget);
      const $parent = $currentTarget.parent();
      if ($parent.hasClass('ckeditor-button') || $parent.hasClass('ckeditor-button-separator')) {
        this.announceButtonPosition($currentTarget.parent());
      }
      else if ($originalTarget.attr('role') !== 'button' && $currentTarget.hasClass('ckeditor-toolbar-group')) {
        this.announceButtonGroupPosition($currentTarget);
      }
    },

    /**
     * Announces the current position of a button group.
     *
     * @param {jQuery} $group
     *   A jQuery set that contains an li element that wraps a group of buttons.
     */
    announceButtonGroupPosition($group) {
      const $groups = $group.parent().children();
      const $row = $group.closest('.ckeditor-row');
      const $rows = $row.parent().children();
      const position = $groups.index($group) + 1;
      const positionCount = $groups.not('.placeholder').length;
      const row = $rows.index($row) + 1;
      const rowCount = $rows.not('.placeholder').length;
      let text = Drupal.t('@groupName button group in position @position of @positionCount in row @row of @rowCount.', {
        '@groupName': $group.attr('data-drupal-ckeditor-toolbar-group-name'),
        '@position': position,
        '@positionCount': positionCount,
        '@row': row,
        '@rowCount': rowCount,
      });
      // If this position is the first in the last row then tell the user that
      // pressing the down arrow key will create a new row.
      if (position === 1 && row === rowCount) {
        text += '\n';
        text += Drupal.t('Press the down arrow key to create a new row.');
      }
      Drupal.announce(text, 'assertive');
    },

    /**
     * Announces current button position.
     *
     * @param {jQuery} $button
     *   A jQuery set that contains an li element that wraps a button.
     */
    announceButtonPosition($button) {
      const $row = $button.closest('.ckeditor-row');
      const $rows = $row.parent().children();
      const $buttons = $button.closest('.ckeditor-buttons').children();
      const $group = $button.closest('.ckeditor-toolbar-group');
      const $groups = $group.parent().children();
      const groupPosition = $groups.index($group) + 1;
      const groupPositionCount = $groups.not('.placeholder').length;
      const position = $buttons.index($button) + 1;
      const positionCount = $buttons.length;
      const row = $rows.index($row) + 1;
      const rowCount = $rows.not('.placeholder').length;
      // The name of the button separator is 'button separator' and its type
      // is 'separator', so we do not want to print the type of this item,
      // otherwise the UA will speak 'button separator separator'.
      const type = ($button.attr('data-drupal-ckeditor-type') === 'separator') ? '' : Drupal.t('button');
      let text;
      // The button is located in the available button set.
      if ($button.closest('.ckeditor-toolbar-disabled').length > 0) {
        text = Drupal.t('@name @type.', {
          '@name': $button.children().attr('aria-label'),
          '@type': type,
        });
        text += `\n${Drupal.t('Press the down arrow key to activate.')}`;

        Drupal.announce(text, 'assertive');
      }
      // The button is in the active toolbar.
      else if ($group.not('.placeholder').length === 1) {
        text = Drupal.t('@name @type in position @position of @positionCount in @groupName button group in row @row of @rowCount.', {
          '@name': $button.children().attr('aria-label'),
          '@type': type,
          '@position': position,
          '@positionCount': positionCount,
          '@groupName': $group.attr('data-drupal-ckeditor-toolbar-group-name'),
          '@row': row,
          '@rowCount': rowCount,
        });
        // If this position is the first in the last row then tell the user that
        // pressing the down arrow key will create a new row.
        if (groupPosition === 1 && position === 1 && row === rowCount) {
          text += '\n';
          text += Drupal.t('Press the down arrow key to create a new button group in a new row.');
        }
        // If this position is the last one in this row then tell the user that
        // moving the button to the next group will create a new group.
        if (groupPosition === groupPositionCount && position === positionCount) {
          text += '\n';
          text += Drupal.t('This is the last group. Move the button forward to create a new group.');
        }
        Drupal.announce(text, 'assertive');
      }
    },

    /**
     * Provides help information when a button is clicked.
     *
     * @param {jQuery.Event} event
     *   The click event for the button click.
     */
    announceButtonHelp(event) {
      const $link = $(event.currentTarget);
      const $button = $link.parent();
      const enabled = $button.closest('.ckeditor-toolbar-active').length > 0;
      let message;

      if (enabled) {
        message = Drupal.t('The "@name" button is currently enabled.', {
          '@name': $link.attr('aria-label'),
        });
        message += `\n${Drupal.t('Use the keyboard arrow keys to change the position of this button.')}`;
        message += `\n${Drupal.t('Press the up arrow key on the top row to disable the button.')}`;
      }
      else {
        message = Drupal.t('The "@name" button is currently disabled.', {
          '@name': $link.attr('aria-label'),
        });
        message += `\n${Drupal.t('Use the down arrow key to move this button into the active toolbar.')}`;
      }
      Drupal.announce(message);
      event.preventDefault();
    },

    /**
     * Provides help information when a separator is clicked.
     *
     * @param {jQuery.Event} event
     *   The click event for the separator click.
     */
    announceSeparatorHelp(event) {
      const $link = $(event.currentTarget);
      const $button = $link.parent();
      const enabled = $button.closest('.ckeditor-toolbar-active').length > 0;
      let message;

      if (enabled) {
        message = Drupal.t('This @name is currently enabled.', {
          '@name': $link.attr('aria-label'),
        });
        message += `\n${Drupal.t('Use the keyboard arrow keys to change the position of this separator.')}`;
      }
      else {
        message = Drupal.t('Separators are used to visually split individual buttons.');
        message += `\n${Drupal.t('This @name is currently disabled.', {
          '@name': $link.attr('aria-label'),
        })}`;
        message += `\n${Drupal.t('Use the down arrow key to move this separator into the active toolbar.')}`;
        message += `\n${Drupal.t('You may add multiple separators to each button group.')}`;
      }
      Drupal.announce(message);
      event.preventDefault();
    },
  });
}(Drupal, Backbone, jQuery));
