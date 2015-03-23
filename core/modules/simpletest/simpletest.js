(function ($) {

  "use strict";

  /**
   * Collapses table rows followed by group rows on the test listing page.
   */
  Drupal.behaviors.simpleTestGroupCollapse = {
    attach: function (context) {
      $(context).find('.simpletest-group').once('simpletest-group-collapse').each(function () {
        var $group = $(this);
        var $image = $group.find('.simpletest-image');
        $image
          .html(drupalSettings.simpleTest.images[0])
          .on('click', function () {
            var $tests = $group.nextUntil('.simpletest-group');
            var expand = !$group.hasClass('expanded');
            $group.toggleClass('expanded', expand);
            $tests.toggleClass('js-hide', !expand);
            $image.html(drupalSettings.simpleTest.images[+expand]);
          });
      });
    }
  };

  /**
   * Toggles test checkboxes to match the group checkbox.
   */
  Drupal.behaviors.simpleTestSelectAll = {
    attach: function (context) {
      $(context).find('.simpletest-group').once('simpletest-group-select-all').each(function () {
        var $group = $(this);
        var $cell = $group.find('.simpletest-group-select-all');
        var $groupCheckbox = $('<input type="checkbox" id="' + $cell.attr('id') + '-group-select-all" class="form-checkbox" />');
        var $testCheckboxes = $group.nextUntil('.simpletest-group').find('input[type=checkbox]');
        $cell.append($groupCheckbox);

        // Toggle the test checkboxes when the group checkbox is toggled.
        $groupCheckbox.on('change', function () {
          var checked = $(this).prop('checked');
          $testCheckboxes.prop('checked', checked);
        });

        // Update the group checkbox when a test checkbox is toggled.
        function updateGroupCheckbox() {
          var allChecked = true;
          $testCheckboxes.each(function () {
            if (!$(this).prop('checked')) {
              allChecked = false;
              return false;
            }
          });
          $groupCheckbox.prop('checked', allChecked);
        }

        $testCheckboxes.on('change', updateGroupCheckbox);
      });
    }
  };

  /**
   * Filters the test list table by a text input search string.
   *
   * Text search input: input.table-filter-text
   * Target table:      input.table-filter-text[data-table]
   * Source text:       .table-filter-text-source
   */
  Drupal.behaviors.simpletestTableFilterByText = {
    attach: function (context) {
      var $input = $('input.table-filter-text').once('table-filter-text');
      var $table = $($input.attr('data-table'));
      var $rows;
      var searched = false;

      function filterTestList(e) {
        var query = $(e.target).val().toLowerCase();

        function showTestRow(index, row) {
          var $row = $(row);
          var $sources = $row.find('.table-filter-text-source');
          var textMatch = $sources.text().toLowerCase().indexOf(query) !== -1;
          $row.closest('tr').toggle(textMatch);
        }

        // Filter if the length of the query is at least 3 characters.
        if (query.length >= 3) {
          // Indicate that a search has been performed, and hide the "select all"
          // checkbox.
          searched = true;
          $('#simpletest-form-table thead th.select-all input').hide();

          $rows.each(showTestRow);
        }
        // Restore to the original state if any searching has occurred.
        else if (searched) {
          searched = false;
          $('#simpletest-form-table thead th.select-all input').show();
          // Restore all rows to their original display state.
          $rows.css('display', '');
        }
      }

      if ($table.length) {
        $rows = $table.find('tbody tr');
        $input.trigger('focus').on('keyup', Drupal.debounce(filterTestList, 200));
      }
    }
  };

})(jQuery);
