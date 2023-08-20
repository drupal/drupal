/**
 * @file
 * User permission page behaviors.
 */

(function ($, Drupal, debounce) {
  /**
   * Shows checked and disabled checkboxes for inherited permissions.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches functionality to the permissions table.
   */
  Drupal.behaviors.permissions = {
    attach(context) {
      once('permissions', 'table#permissions').forEach((table) => {
        // On a site with many roles and permissions, this behavior initially
        // has to perform thousands of DOM manipulations to inject checkboxes
        // and hide them. By detaching the table from the DOM, all operations
        // can be performed without triggering internal layout and re-rendering
        // processes in the browser.
        const $table = $(table);
        let $ancestor;
        let method;
        if ($table.prev().length) {
          $ancestor = $table.prev();
          method = 'after';
        } else {
          $ancestor = $table.parent();
          method = 'append';
        }
        $table.detach();

        // Create dummy checkboxes. We use dummy checkboxes instead of reusing
        // the existing checkboxes here because new checkboxes don't alter the
        // submitted form. If we'd automatically check existing checkboxes, the
        // permission table would be polluted with redundant entries. This is
        // deliberate, but desirable when we automatically check them.
        const $dummy = $(Drupal.theme('checkbox'))
          .removeClass('form-checkbox')
          .addClass('dummy-checkbox js-dummy-checkbox')
          .attr('disabled', 'disabled')
          .attr('checked', 'checked')
          .attr(
            'title',
            Drupal.t(
              'This permission is inherited from the authenticated user role.',
            ),
          )
          .hide();

        $table
          .find('input[type="checkbox"]')
          .not('.js-rid-anonymous, .js-rid-authenticated')
          .addClass('real-checkbox js-real-checkbox')
          .after($dummy);

        // Initialize the authenticated user checkbox.
        $table
          .find('input[type=checkbox].js-rid-authenticated')
          .on('click.permissions', this.toggle)
          // .triggerHandler() cannot be used here, as it only affects the first
          // element.
          .each(this.toggle);

        // Re-insert the table into the DOM.
        $ancestor[method]($table);
      });
    },

    /**
     * Toggles all dummy checkboxes based on the checkboxes' state.
     *
     * If the "authenticated user" checkbox is checked, the checked and disabled
     * checkboxes are shown, the real checkboxes otherwise.
     */
    toggle() {
      const authCheckbox = this;
      const $row = $(this).closest('tr');
      // jQuery performs too many layout calculations for .hide() and .show(),
      // leading to a major page rendering lag on sites with many roles and
      // permissions. Therefore, we toggle visibility directly.
      $row.find('.js-real-checkbox').each(function () {
        this.style.display = authCheckbox.checked ? 'none' : '';
      });
      $row.find('.js-dummy-checkbox').each(function () {
        this.style.display = authCheckbox.checked ? '' : 'none';
      });
    },
  };

  /**
   * Filters the permission list table by a text input search string.
   *
   * Text search input: input.table-filter-text
   * Target table:      input.table-filter-text[data-table]
   * Source text:       .table-filter-text-source
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.tableFilterByText = {
    attach(context, settings) {
      const [input] = once('table-filter-text', 'input.table-filter-text');
      if (!input) {
        return;
      }
      const tableSelector = input.getAttribute('data-table');
      const $table = $(tableSelector);
      const $rows = $table.find('tbody tr');

      function hideEmptyPermissionHeader(index, row) {
        const tdsWithModuleClass = row.querySelectorAll('td.module');
        // Function to check if an element is visible (`display: block`).
        function isVisible(element) {
          return getComputedStyle(element).display !== 'none';
        }
        if (tdsWithModuleClass.length > 0) {
          // Find the next visible sibling `<tr>`.
          let nextVisibleSibling = row.nextElementSibling;
          while (nextVisibleSibling && !isVisible(nextVisibleSibling)) {
            nextVisibleSibling = nextVisibleSibling.nextElementSibling;
          }

          // Check if the next visible sibling has the "module" class in any of
          // its `<td>` elements.
          let nextVisibleSiblingHasModuleClass = false;
          if (nextVisibleSibling) {
            const nextSiblingTdsWithModuleClass =
              nextVisibleSibling.querySelectorAll('td.module');
            nextVisibleSiblingHasModuleClass =
              nextSiblingTdsWithModuleClass.length > 0;
          }

          // Check if this is the last visible row with class "module".
          const isLastVisibleModuleRow =
            !nextVisibleSibling || !isVisible(nextVisibleSibling);

          // Hide the current row with class "module" if it meets the
          // conditions.
          $(row).toggle(
            !nextVisibleSiblingHasModuleClass && !isLastVisibleModuleRow,
          );
        }
      }

      function filterPermissionList(e) {
        const query = e.target.value;
        if (query.length === 0) {
          // Reset table when the textbox is cleared.
          $rows.show();
        }
        // Case insensitive expression to find query at the beginning of a word.
        const re = new RegExp(`\\b${query}`, 'i');

        function showPermissionRow(index, row) {
          const sources = row.querySelectorAll('.table-filter-text-source');
          if (sources.length > 0) {
            const textMatch = sources[0].textContent.search(re) !== -1;
            $(row).closest('tr').toggle(textMatch);
          }
        }
        // Search over all rows.
        $rows.show();

        // Filter if the length of the query is at least 2 characters.
        if (query.length >= 2) {
          $rows.each(showPermissionRow);

          // Hide the empty header if they don't have any visible rows.
          const visibleRows = $table.find('tbody tr:visible');
          visibleRows.each(hideEmptyPermissionHeader);
          const rowsWithoutEmptyModuleName = $table.find('tbody tr:visible');
          // Find elements with class "permission" within visible rows.
          const tdsWithModuleOrPermissionClass =
            rowsWithoutEmptyModuleName.find('.permission');

          Drupal.announce(
            Drupal.formatPlural(
              tdsWithModuleOrPermissionClass.length,
              '1 permission is available in the modified list.',
              '@count permissions are available in the modified list.',
            ),
          );
        }
      }

      function preventEnterKey(event) {
        if (event.which === 13) {
          event.preventDefault();
          event.stopPropagation();
        }
      }

      if ($table.length) {
        $(input).on({
          keyup: debounce(filterPermissionList, 200),
          click: debounce(filterPermissionList, 200),
          keydown: preventEnterKey,
        });
      }
    },
  };
})(jQuery, Drupal, Drupal.debounce);
