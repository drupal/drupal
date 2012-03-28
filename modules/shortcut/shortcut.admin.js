(function ($) {

/**
 * Handle the concept of a fixed number of slots.
 *
 * This behavior is dependent on the tableDrag behavior, since it uses the
 * objects initialized in that behavior to update the row.
 */
Drupal.behaviors.shortcutDrag = {
  attach: function (context, settings) {
    if (Drupal.tableDrag) {
      var table = $('table#shortcuts'),
        visibleLength = 0,
        slots = 0,
        tableDrag = Drupal.tableDrag.shortcuts;
      $('> tbody > tr, > tr', table)
        .filter(':visible')
          .filter(':odd').filter('.odd')
            .removeClass('odd').addClass('even')
          .end().end()
          .filter(':even').filter('.even')
            .removeClass('even').addClass('odd')
          .end().end()
        .end()
        .filter('.shortcut-slot-empty').each(function(index) {
          if ($(this).is(':visible')) {
            visibleLength++;
          }
          slots++;
        });

      // Add a handler for when a row is swapped.
      tableDrag.row.prototype.onSwap = function (swappedRow) {
        var disabledIndex = $(table).find('tr').index($(table).find('tr.shortcut-status-disabled')) - slots - 2,
          count = 0;
        $(table).find('tr.shortcut-status-enabled').nextAll(':not(.shortcut-slot-empty)').each(function(index) {
          if (index < disabledIndex) {
            count++;
          }
        });
        var total = slots - count;
        if (total == -1) {
          var disabled = $(table).find('tr.shortcut-status-disabled');
          // To maintain the shortcut links limit, we need to move the last
          // element from the enabled section to the disabled section.
          var changedRow = disabled.prevAll(':not(.shortcut-slot-empty)').not($(this.element)).get(0);
          disabled.after(changedRow);
          if ($(changedRow).hasClass('draggable')) {
            // The dropped element will automatically be marked as changed by
            // the tableDrag system. However, the row that swapped with it
            // has moved to the "disabled" section, so we need to force its
            // status to be disabled and mark it also as changed.
            var changedRowObject = new tableDrag.row(changedRow, 'mouse', self.indentEnabled, self.maxDepth, true);
            changedRowObject.markChanged();
            tableDrag.rowStatusChange(changedRowObject);
          }
        }
        else if (total != visibleLength) {
          if (total > visibleLength) {
            // Less slots on screen than needed.
            $('.shortcut-slot-empty:hidden:last').show();
            visibleLength++;
          }
          else {
            // More slots on screen than needed.
            $('.shortcut-slot-empty:visible:last').hide();
            visibleLength--;
          }
        }
      };

      // Add a handler so when a row is dropped, update fields dropped into new regions.
      tableDrag.onDrop = function () {
        tableDrag.rowStatusChange(this.rowObject);
        return true;
      };

      tableDrag.rowStatusChange = function (rowObject) {
        // Use "status-message" row instead of "status" row because
        // "status-{status_name}-message" is less prone to regexp match errors.
        var statusRow = $(rowObject.element).prevAll('tr.shortcut-status').get(0);
        var statusName = statusRow.className.replace(/([^ ]+[ ]+)*shortcut-status-([^ ]+)([ ]+[^ ]+)*/, '$2');
        var statusField = $('select.shortcut-status-select', rowObject.element);
        statusField.val(statusName);
      };

      tableDrag.restripeTable = function () {
        // :even and :odd are reversed because jQuery counts from 0 and
        // we count from 1, so we're out of sync.
        // Match immediate children of the parent element to allow nesting.
        $('> tbody > tr:visible, > tr:visible', this.table)
          .filter(':odd').filter('.odd')
            .removeClass('odd').addClass('even')
          .end().end()
          .filter(':even').filter('.even')
            .removeClass('even').addClass('odd');
      };
    }
  }
};

/**
 * Make it so when you enter text into the "New set" textfield, the
 * corresponding radio button gets selected.
 */
Drupal.behaviors.newSet = {
  attach: function (context, settings) {
    var selectDefault = function() {
      $(this).closest('form').find('.form-item-set .form-type-radio:last input').attr('checked', 'checked');
    };
    $('div.form-item-new input').focus(selectDefault).keyup(selectDefault);
  }
};

})(jQuery);
