(function ($, Drupal, drupalSettings) {

"use strict";

Drupal.ckeditor = Drupal.ckeditor || {};

// Aria-live element for speaking application state.
var $messages;

Drupal.behaviors.ckeditorAdmin = {
  attach: function (context) {
    var $context = $(context);
    var $ckeditorToolbar = $context.find('.ckeditor-toolbar-configuration').once('ckeditor-toolbar');

    /**
     * Event callback for keypress. Move buttons based on arrow keys.
     */
    function adminToolbarMoveButton (event) {
      var $target = $(event.currentTarget);
      var label = Drupal.t('@label button', { '@label': $target.attr('aria-label') });
      var $button = $target.parent();
      var $currentRow = $button.closest('.ckeditor-buttons');
      var $destinationRow = null;
      var destinationPosition = $button.index();

      switch (event.keyCode) {
        case 37: // Left arrow.
        case 63234: // Safari left arrow.
          $destinationRow = $currentRow;
          destinationPosition -= rtl;
          break;

        case 38: // Up arrow.
        case 63232: // Safari up arrow.
          $destinationRow = $($toolbarRows[$toolbarRows.index($currentRow) - 1]);
          break;

        case 39: // Right arrow.
        case 63235: // Safari right arrow.
          $destinationRow = $currentRow;
          destinationPosition += rtl;
          break;

        case 40: // Down arrow.
        case 63233: // Safari down arrow.
          $destinationRow = $($toolbarRows[$toolbarRows.index($currentRow) + 1]);
      }

      if ($destinationRow && $destinationRow.length) {
        // Detach the button from the DOM so its position doesn't interfere.
        $button.detach();
        // Move the button before the button whose position it should occupy.
        var $targetButton = $destinationRow.children(':eq(' + destinationPosition + ')');
        if ($targetButton.length) {
          $targetButton.before($button);
        }
        else {
          $destinationRow.append($button);
        }
        // Post the update to the aria-live message element.
        $messages.text(Drupal.t('moved to @row, position @position of @totalPositions', {
          '@row': getRowInfo($destinationRow),
          '@position': (destinationPosition + 1),
          '@totalPositions': $destinationRow.children().length
        }));
        // Update the toolbar value field.
        adminToolbarValue(event, { item: $button });
      }
      event.preventDefault();
    }

    /**
     * Event callback for keyup. Move a separator into the active toolbar.
     */
    function adminToolbarMoveSeparator (event) {
      switch (event.keyCode) {
        case 38: // Up arrow.
        case 63232: // Safari up arrow.
          var $button = $(event.currentTarget).parent().clone().appendTo($toolbarRows.eq(-2));
          adminToolbarValue(event, { item: $button });
          event.preventDefault();
      }
    }

    /**
     * Provide help when a button is clicked on.
     */
    function adminToolbarButtonHelp (event) {
      var $link = $(event.currentTarget);
      var $button = $link.parent();
      var $currentRow = $button.closest('.ckeditor-buttons');
      var enabled = $button.closest('.ckeditor-toolbar-active').length > 0;
      var position = $button.index() + 1; // 1-based index for humans.
      var rowNumber = $toolbarRows.index($currentRow) + 1;
      var type = event.data.type;
      var message;

      if (enabled) {
        if (type === 'separator') {
          message = Drupal.t('Separators are used to visually split individual buttons. This @name is currently enabled, in row @row and position @position.', { '@name': $link.attr('aria-label'), '@row': rowNumber, '@position': position }) + "\n\n" + Drupal.t('Drag and drop the separator or use the keyboard arrow keys to change the position of this separator.');
        }
        else {
          message = Drupal.t('The @name button is currently enabled, in row @row and position @position.', { '@name': $link.attr('aria-label'), '@row': rowNumber, '@position': position }) + "\n\n" + Drupal.t('Drag and drop the buttons or use the keyboard arrow keys to change the position of this button.');
        }
      }
      else {
        if (type === 'separator') {
          message = Drupal.t('Separators are used to visually split individual buttons. This @name is currently disabled.', { '@name': $link.attr('aria-label') }) + "\n\n" + Drupal.t('Drag the button or use the up arrow key to move this separator into the active toolbar. You may add multiple separators to each row.');
        }
        else {
          message = Drupal.t('The @name button is currently disabled.', { '@name': $link.attr('aria-label') }) + "\n\n" + Drupal.t('Drag the button or use the up arrow key to move this button into the active toolbar.');
        }
      }
      $messages.text(message);
      $link.focus();
      event.preventDefault();
    }

    /**
     * Add a new row of buttons.
     */
    function adminToolbarAddRow (event) {
      var $this = $(event.currentTarget);
      var $rows = $this.closest('.ckeditor-toolbar-active').find('.ckeditor-buttons');
      var $rowNew = $rows.last().clone().empty().sortable(sortableSettings);
      $rows.last().after($rowNew);
      $toolbarRows = $toolbarAdmin.find('.ckeditor-buttons');
      $this.siblings('a').show();
      redrawToolbarGradient();
      // Post the update to the aria-live message element.
      $messages.text(Drupal.t('row number @count added.', {'@count': ($rows.length + 1)}));
      event.preventDefault();
    }

    /**
     * Remove a row of buttons.
     */
    function adminToolbarRemoveRow (event) {
      var $this = $(event.currentTarget);
      var $rows = $this.closest('.ckeditor-toolbar-active').find('.ckeditor-buttons');
      if ($rows.length === 2) {
        $this.hide();
      }
      if ($rows.length > 1) {
        var $lastRow = $rows.last();
        var $disabledButtons = $ckeditorToolbar.find('.ckeditor-toolbar-disabled .ckeditor-buttons');
        $lastRow.children(':not(.ckeditor-multiple-button)').prependTo($disabledButtons);
        $lastRow.sortable('destroy').remove();
        $toolbarRows = $toolbarAdmin.find('.ckeditor-buttons');
        redrawToolbarGradient();
      }
      // Post the update to the aria-live message element.
      $messages.text(Drupal.t('row removed. @count row@plural remaining.', {'@count': ($rows.length - 1), '@plural': ((($rows.length - 1) === 1 ) ? '' : 's')}));
      event.preventDefault();
    }

    /**
     * Browser quirk work-around to redraw CSS3 gradients.
     */
    function redrawToolbarGradient () {
      $ckeditorToolbar.find('.ckeditor-toolbar-active').css('position', 'relative');
      window.setTimeout(function () {
        $ckeditorToolbar.find('.ckeditor-toolbar-active').css('position', '');
      }, 10);
    }

    /**
     * jQuery Sortable stop event. Save updated toolbar positions to the
     * textarea.
     */
    function adminToolbarValue (event, ui) {
      // Update the toolbar config after updating a sortable.
      var toolbarConfig = [];
      var $button = ui.item;
      $button.find('a').focus();
      $ckeditorToolbar.find('.ckeditor-toolbar-active ul').each(function () {
        var $rowButtons = $(this).find('li');
        var rowConfig = [];
        if ($rowButtons.length) {
          $rowButtons.each(function () {
            rowConfig.push(this.getAttribute('data-button-name'));
          });
          toolbarConfig.push(rowConfig);
        }
      });
      $textarea.val(JSON.stringify(toolbarConfig, null, '  '));

      // Determine whether we should trigger an event.
      var from = $(event.target).parents('div[data-toolbar]').attr('data-toolbar');
      var to = $(event.toElement).parents('div[data-toolbar]').attr('data-toolbar');
      if (from !== to) {
        $ckeditorToolbar.find('.ckeditor-toolbar-active')
          .trigger('CKEditorToolbarChanged', [
            (to === 'active') ? 'added' : 'removed',
            ui.item.get(0).getAttribute('data-button-name')
          ]);
      }
    }

    if ($ckeditorToolbar.length) {
      var $textareaWrapper = $ckeditorToolbar.find('.form-item-editor-settings-toolbar-buttons').hide();
      var $textarea = $textareaWrapper.find('textarea');
      var $toolbarAdmin = $(drupalSettings.ckeditor.toolbarAdmin);
      var sortableSettings = {
        connectWith: '.ckeditor-buttons',
        placeholder: 'ckeditor-button-placeholder',
        forcePlaceholderSize: true,
        tolerance: 'pointer',
        cursor: 'move',
        stop: adminToolbarValue
      };
      // Add the toolbar to the page.
      $toolbarAdmin.insertAfter($textareaWrapper);

      // Then determine if this is RTL or not.
      var rtl = $toolbarAdmin.css('direction') === 'rtl' ? -1 : 1;
      var $toolbarRows = $toolbarAdmin.find('.ckeditor-buttons');

      // Add the drag and drop functionality.
      $toolbarRows.sortable(sortableSettings);
      $toolbarAdmin.find('.ckeditor-multiple-buttons li').draggable({
        connectToSortable: '.ckeditor-toolbar-active .ckeditor-buttons',
        helper: 'clone'
      });

      // Add keyboard arrow support.
      $toolbarAdmin.on('keyup.ckeditorMoveButton', '.ckeditor-buttons a', adminToolbarMoveButton);
      $toolbarAdmin.on('keyup.ckeditorMoveSeparator', '.ckeditor-multiple-buttons a', adminToolbarMoveSeparator);

      // Add click for help.
      $toolbarAdmin.on('click.ckeditorClickButton', '.ckeditor-buttons a', { type: 'button' }, adminToolbarButtonHelp);
      $toolbarAdmin.on('click.ckeditorClickSeparator', '.ckeditor-multiple-buttons a', { type: 'separator' }, adminToolbarButtonHelp);

      // Add/remove row button functionality.
      $toolbarAdmin.on('click.ckeditorAddRow', 'a.ckeditor-row-add', adminToolbarAddRow);
      $toolbarAdmin.on('click.ckeditorAddRow', 'a.ckeditor-row-remove', adminToolbarRemoveRow);
      if ($toolbarAdmin.find('.ckeditor-toolbar-active ul').length > 1) {
        $toolbarAdmin.find('a.ckeditor-row-remove').hide();
      }

      // Add aural UI focus updates when for individual toolbars.
      $toolbarAdmin.on('focus.ckeditor', '.ckeditor-buttons', grantRowFocus);
      // Identify the aria-live element for interaction updates for screen
      // readers.
      $messages = $('#ckeditor-button-configuration-aria-live');
    }
  }
};

/**
 * Returns a string describing the type and index of a toolbar row.
 *
 * @param {jQuery} $row
 *   A jQuery object containing a .ckeditor-button row.
 *
 * @return {String}
 *   A string describing the type and index of a toolbar row.
 */
function getRowInfo ($row) {
  var output = '';
  var row;
  // Determine if this is an active row or an available row.
  if ($row.closest('.ckeditor-toolbar-disabled').length > 0) {
    row = $('.ckeditor-toolbar-disabled').find('.ckeditor-buttons').index($row) + 1;
    output += Drupal.t('available button row @row', {'@row': row});
  }
  else {
    row = $('.ckeditor-toolbar-active').find('.ckeditor-buttons').index($row) + 1;
    output += Drupal.t('active button row @row', {'@row': row});
  }
  return output;
}

/**
 * Applies or removes the focused class to a toolbar row.
 *
 * When a button in a toolbar is focused, focus is triggered on the containing
 * toolbar row. When a row is focused, the state change is announced through
 * the aria-live message area.
 *
 * @param {jQuery} event
 *   A jQuery event.
 */
function grantRowFocus (event) {
  var $row = $(event.currentTarget);
  // Remove the focused class from all other toolbars.
  $('.ckeditor-buttons.focused').not($row).removeClass('focused');
  // Post the update to the aria-live message element.
  if (!$row.hasClass('focused')) {
    // Indicate that the current row has focus.
    $row.addClass('focused');
    $messages.text(Drupal.t('@row', {'@row': getRowInfo($row)}));
  }
}

})(jQuery, Drupal, drupalSettings);
