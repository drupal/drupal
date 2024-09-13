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
    attach() {
      const [table] = once('permissions', 'table#permissions');
      if (!table) {
        return;
      }

      // Create fake checkboxes. We use fake checkboxes instead of reusing
      // the existing checkboxes here because new checkboxes don't alter the
      // submitted form. If we'd automatically check existing checkboxes, the
      // permission table would be polluted with redundant entries. This is
      // deliberate, but desirable when we automatically check them.
      const $fakeCheckbox = $(Drupal.theme('checkbox'))
        .removeClass('form-checkbox')
        .addClass('fake-checkbox js-fake-checkbox')
        .attr({
          disabled: 'disabled',
          checked: 'checked',
          title: Drupal.t(
            'This permission is inherited from the authenticated user role.',
          ),
        });
      const $wrapper = $('<div></div>').append($fakeCheckbox);
      const fakeCheckboxHtml = $wrapper.html();

      /**
       * Process each table row to create fake checkboxes.
       *
       * @param {object} object
       * @param {HTMLElement} object.target
       */
      function tableRowProcessing({ target }) {
        once('permission-checkbox', target).forEach((checkbox) => {
          checkbox
            .closest('tr')
            .querySelectorAll(
              'input[type="checkbox"]:not(.js-rid-anonymous, .js-rid-authenticated)',
            )
            .forEach((check) => {
              check.classList.add('real-checkbox', 'js-real-checkbox');
              check.insertAdjacentHTML('beforebegin', fakeCheckboxHtml);
            });
        });
      }

      // An IntersectionObserver object is associated with each of the table
      // rows to activate checkboxes interactively as users scroll the page
      // up or down. This prevents processing all checkboxes on page load.
      const checkedCheckboxObserver = new IntersectionObserver(
        (entries, thisObserver) => {
          entries
            .filter((entry) => entry.isIntersecting)
            .forEach((entry) => {
              tableRowProcessing(entry);
              thisObserver.unobserve(entry.target);
            });
        },
        {
          rootMargin: '50%',
        },
      );

      // Select rows with checked authenticated role and attach an observer
      // to each.
      table
        .querySelectorAll(
          'tbody tr input[type="checkbox"].js-rid-authenticated:checked',
        )
        .forEach((checkbox) => checkedCheckboxObserver.observe(checkbox));

      // Create checkboxes only when necessary on click.
      $(table).on(
        'click.permissions',
        'input[type="checkbox"].js-rid-authenticated',
        tableRowProcessing,
      );
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
