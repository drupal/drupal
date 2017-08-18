/**
 * @file
 * Simpletest behaviors.
 */

(function ($, Drupal, drupalSettings) {
  /**
   * Collapses table rows followed by group rows on the test listing page.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attach collapse behavior on the test listing page.
   */
  Drupal.behaviors.simpleTestGroupCollapse = {
    attach(context) {
      $(context).find('.simpletest-group').once('simpletest-group-collapse').each(function () {
        const $group = $(this);
        const $image = $group.find('.simpletest-image');
        $image
          .html(drupalSettings.simpleTest.images[0])
          .on('click', () => {
            const $tests = $group.nextUntil('.simpletest-group');
            const expand = !$group.hasClass('expanded');
            $group.toggleClass('expanded', expand);
            $tests.toggleClass('js-hide', !expand);
            $image.html(drupalSettings.simpleTest.images[+expand]);
          });
      });
    },
  };

  /**
   * Toggles test checkboxes to match the group checkbox.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches behavior for selecting all tests in a group.
   */
  Drupal.behaviors.simpleTestSelectAll = {
    attach(context) {
      $(context).find('.simpletest-group').once('simpletest-group-select-all').each(function () {
        const $group = $(this);
        const $cell = $group.find('.simpletest-group-select-all');
        const $groupCheckbox = $(`<input type="checkbox" id="${$cell.attr('id')}-group-select-all" class="form-checkbox" />`);
        const $testCheckboxes = $group.nextUntil('.simpletest-group').find('input[type=checkbox]');
        $cell.append($groupCheckbox);

        // Toggle the test checkboxes when the group checkbox is toggled.
        $groupCheckbox.on('change', function () {
          const checked = $(this).prop('checked');
          $testCheckboxes.prop('checked', checked);
        });

        // Update the group checkbox when a test checkbox is toggled.
        function updateGroupCheckbox() {
          let allChecked = true;
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
    },
  };

  /**
   * Filters the test list table by a text input search string.
   *
   * Text search input: input.table-filter-text
   * Target table:      input.table-filter-text[data-table]
   * Source text:       .table-filter-text-source
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the filter behavior to the text input element.
   */
  Drupal.behaviors.simpletestTableFilterByText = {
    attach(context) {
      const $input = $('input.table-filter-text').once('table-filter-text');
      const $table = $($input.attr('data-table'));
      let $rows;
      let searched = false;

      function filterTestList(e) {
        const query = $(e.target).val().toLowerCase();

        function showTestRow(index, row) {
          const $row = $(row);
          const $sources = $row.find('.table-filter-text-source');
          const textMatch = $sources.text().toLowerCase().indexOf(query) !== -1;
          $row.closest('tr').toggle(textMatch);
        }

        // Filter if the length of the query is at least 3 characters.
        if (query.length >= 3) {
          // Indicate that a search has been performed, and hide the
          // "select all" checkbox.
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
    },
  };
}(jQuery, Drupal, drupalSettings));
