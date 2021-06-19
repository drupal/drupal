/**
 * @file
 * Provide dragging capabilities to admin uis.
 */

/**
 * Triggers when weights columns are toggled.
 *
 * @event columnschange
 */

(function ($, Drupal, drupalSettings) {
  /**
   * Store the state of weight columns display for all tables.
   *
   * Default value is to hide weight columns.
   */
  let showWeight = JSON.parse(
    localStorage.getItem('Drupal.tableDrag.showWeight'),
  );

  /**
   * Drag and drop table rows with field manipulation.
   *
   * Using the drupal_attach_tabledrag() function, any table with weights or
   * parent relationships may be made into draggable tables. Columns containing
   * a field may optionally be hidden, providing a better user experience.
   *
   * Created tableDrag instances may be modified with custom behaviors by
   * overriding the .onDrag, .onDrop, .row.onSwap, and .row.onIndent methods.
   * See blocks.js for an example of adding additional functionality to
   * tableDrag.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.tableDrag = {
    attach(context, settings) {
      function initTableDrag(table, base) {
        if (table.length) {
          // Create the new tableDrag instance. Save in the Drupal variable
          // to allow other scripts access to the object.
          Drupal.TableDrag.instances[base] = new Drupal.TableDrag(
            table[0],
            settings.tableDrag[base],
          );
        }
      }

      Object.keys(settings.tableDrag || {}).forEach((base) => {
        initTableDrag($(context).find(`#${base}`).once('tabledrag'), base);
      });
    },
  };

  Drupal.TableDrag = class {
    constructor(table, tableSettings) {
      this.table = table;
      this.table.setAttribute('data-drupal-tabledrag-table', '');
      this.tableSettings = tableSettings;

      // Used to hold information about a current drag operation.
      this.dragObject = null;

      // Provides operations for row manipulation.
      this.rowObject = null;

      // Remember the previous element.
      this.oldRowElement = null;

      // Used to determine up or down direction from last mouse move.
      this.oldY = null;

      // Whether anything in the entire table has changed.
      this.changed = false;

      // Maximum amount of allowed parenting.
      this.maxDepth = 0;

      // Direction of the table.
      this.rtl =
        window.getComputedStyle(this.table).getPropertyValue('direction') ===
        'rtl'
          ? -1
          : 1;

      this.striping = this.table.getAttribute('data-striping') === '1';
      this.scrollSettings = { amount: 4, interval: 50, trigger: 70 };
      this.scrollInterval = null;
      this.scrollY = 0;
      this.windowHeight = 0;
      this.toggleWeightButton = null;

      /**
       * Check this table's settings for parent relationships.
       *
       * For efficiency, large sections of code can be skipped if we don't need to
       * track horizontal movement and indentations.
       */
      this.indentEnabled = false;
      Object.keys(tableSettings || {}).forEach((group) => {
        Object.keys(tableSettings[group] || {}).forEach((n) => {
          if (tableSettings[group][n].relationship === 'parent') {
            this.indentEnabled = true;
          }
          if (tableSettings[group][n].limit > 0) {
            this.maxDepth = tableSettings[group][n].limit;
          }
        });
      });
      if (this.indentEnabled) {
        /**
         * Total width of indents, set in makeDraggable.
         *
         * @type {number}
         */
        this.indentCount = 1;

        // Find the width of indentations to measure mouse movements against.
        // Because the table doesn't need to start with any indentations, we
        // manually append 2 indentations in the first draggable row, measure
        // the offset, then remove.
        const indent = this.constructor.stringToElement(
          Drupal.theme('tableDragIndentation'),
        );
        const testRow = document.createElement('tr');
        this.table.appendChild(testRow);
        const testCell = document.createElement('td');
        testRow.appendChild(testCell);
        testCell.appendChild(indent);
        testCell.appendChild(indent.cloneNode(true));
        const indentations = testCell.querySelectorAll('.js-indentation');
        this.indentAmount =
          indentations[1].getBoundingClientRect().x -
          indentations[0].getBoundingClientRect().x;
        testRow.parentNode.removeChild(testRow);
      }

      // Make each applicable row draggable.
      // Match immediate children of the parent element to allow nesting.
      // @todo jquery :scope unsupported in IE11 https://drupal.org/node/3176438
      this.table
        .querySelectorAll(
          ':scope > tr.draggable, :scope > tbody > tr.draggable',
        )
        .forEach((row) => {
          this.makeDraggable(row);
        });

      const toggleWeightWrapper = this.constructor.stringToElement(
        Drupal.theme('tableDragToggle'),
      );
      this.toggleWeightButton = toggleWeightWrapper.querySelector(
        '[data-drupal-selector="tabledrag-toggle-weight"]',
      );
      $(this.toggleWeightButton).on('click', (e) => {
        e.preventDefault();
        this.toggleColumns();
      });
      this.table.parentNode.insertBefore(toggleWeightWrapper, this.table);

      // Initialize the specified columns (for example, weight or parent columns)
      // to show or hide according to user preference. This aids accessibility
      // so that, e.g., screen reader users can choose to enter weight values and
      // manipulate form elements directly, rather than using drag-and-drop..
      this.initColumns();

      // Add event bindings to the document.
      $(document).on('touchmove', (event) =>
        this.dragRow(event.originalEvent.touches[0]),
      );
      $(document).on('touchend', (event) =>
        this.dropRow(event.originalEvent.touches[0]),
      );
      $(document).on('mousemove pointermove', (event) => this.dragRow(event));
      $(document).on('mouseup pointerup', (event) => this.dropRow(event));

      $(window).on('storage', (e) => {
        // Only react to 'Drupal.tableDrag.showWeight' value change.
        if (e.originalEvent.key === 'Drupal.tableDrag.showWeight') {
          // This was changed in another window, get the new value for this
          // window.
          showWeight = JSON.parse(e.originalEvent.newValue);
          this.displayColumns(showWeight);
        }
      });
    }

    /**
     * Helper function to change a string to a Node.
     *
     * @param {string} string
     *   A string of markup.
     * @return {ChildNode}
     *   The string as a Node object.
     *
     * @todo jquery: should this be available globally instead?
     */
    static stringToElement(string) {
      const parser = new DOMParser();
      const elementContainer = parser.parseFromString(string, 'text/html');
      return elementContainer.body.firstChild;
    }

    /**
     * Helper that replicates jQuery prev().
     *
     * @param {HTMLElement} element
     *   Element being matched.
     * @param {string|null }selector
     *   Selector of elements to match.
     * @return {Element|null}
     *   The previous element.
     *
     * @todo jQuery: consider a global replacement for jQuery prev() instead
     */
    static previous(element, selector) {
      // Get the next sibling element
      let sibling = element.previousElementSibling;

      // If there's no selector, return the first sibling
      if (!selector) {
        return sibling;
      }

      // If the sibling matches our selector, use it
      // If not, jump to the next sibling and continue the loop
      while (sibling) {
        if (sibling.matches(selector)) {
          return sibling;
        }
        sibling = sibling.previousElementSibling;
      }
    }

    /**
     * Helper that replicates jQuery next().
     *
     * @param {HTMLElement} element
     *   Element being matched.
     * @param {string|null }selector
     *   Selector of elements to match.
     * @return {Element|null}
     *   The next element.
     *
     * @todo jQuery: consider a global replacement for jQuery next() instead
     */
    static next(element, selector) {
      // Get the next sibling element
      let sibling = element.nextElementSibling;

      // If there's no selector, return the first sibling
      if (!selector) {
        return sibling;
      }

      // If the sibling matches our selector, use it
      // If not, jump to the next sibling and continue the loop
      while (sibling) {
        if (sibling.matches(selector)) {
          return sibling;
        }
        sibling = sibling.nextElementSibling;
      }
    }

    /**
     * Helper that replicates jQuery prevAll().
     *
     * @param {HTMLElement} element
     *   Element being checked against.
     * @param {string|null }selector
     *   Selector of elements to match.
     * @return {Element|null}
     *   The previous elements.
     *
     * @todo jQuery: consider a global replacement for jQuery prevAll() instead
     */
    static prevAll(element, selector) {
      let prevElement = element.previousElementSibling;
      const result = [];

      while (prevElement) {
        if (selector === null || prevElement.matches(selector)) {
          result.push(prevElement);
        }

        prevElement = prevElement.previousElementSibling;
      }
      return result;
    }

    /**
     * Helper that replicates the jQuery :hidden selector.
     *
     * @param {HTMLElement} element
     *    The element being checked
     * @return {boolean}
     *    If the element is hidden.
     *
     * @todo jQuery: consider a global replacement for jQuery :hidden
     * instead of this.
     */
    static isHidden(element) {
      if (!element) {
        return false;
      }

      do {
        if (element instanceof Element) {
          const style = window.getComputedStyle(element);
          if (
            style.width === '0' ||
            style.height === '0' ||
            style.opacity === '0' ||
            style.display === 'none' ||
            style.visibility === 'hidden' ||
            element.hidden
          ) {
            return true;
          }
        }

        element = element.parentNode;
      } while (element);

      return false;
    }

    /**
     * Helper that replicates the jQuery :visible selector.
     *
     * @param {HTMLElement} element
     *    The element being checked
     * @return {boolean}
     *    If the element is visible.
     *
     * @todo jQuery: consider a global replacement for jQuery :visible
     * instead of this.
     */
    static isVisible(element) {
      return !!(
        element.offsetWidth ||
        element.offsetHeight ||
        element.getClientRects().length
      );
    }

    /**
     * Initialize columns containing form elements to be hidden by default.
     *
     * Identify and mark each cell with a CSS class so we can easily toggle
     * show/hide it. Finally, hide columns if user does not have a
     * 'Drupal.tableDrag.showWeight' localStorage value.
     */
    initColumns() {
      let hidden;
      let cell;

      Object.keys(this.tableSettings || {}).forEach((group) => {
        // Find the first field in this group.
        Object.keys(this.tableSettings[group]).some((tableSetting) => {
          const field = this.table.querySelector(
            `.${this.tableSettings[group][tableSetting].target}`,
          );

          if (field && this.tableSettings[group][tableSetting].hidden) {
            hidden = this.tableSettings[group][tableSetting].hidden;
            cell = field.closest('td');
            return true;
          }
          return false;
        });

        // Mark the column containing this field so it can be hidden.
        if (hidden && cell) {
          // Add 1 to our indexes. The nth-child selector is 1 based, not 0
          // based. Match immediate children of the parent element to allow
          // nesting.
          // @todo jquery :scope unsupported in IE11 https://drupal.org/node/3176438
          const columnIndex =
            Array.prototype.indexOf.call(
              cell.parentNode.querySelectorAll(':scope > td'),
              cell,
            ) + 1;

          // @todo jquery :scope unsupported in IE11 https://drupal.org/node/3176438
          this.table
            .querySelectorAll(
              ':scope > thead > tr, :scope > tbody > tr, :scope > tr',
            )
            .forEach((row) => {
              this.constructor.addColspanClass(row, columnIndex);
            });
        }
      });

      this.displayColumns(showWeight);
    }

    /**
     * Mark cells that have colspan.
     *
     * In order to adjust the colspan instead of hiding them altogether.
     *
     * @param {HTMLElement} row
     *   The row HTML element.
     * @param {number} columnIndex
     *   The column index to add colspan class to.
     */
    static addColspanClass(row, columnIndex) {
      let index = columnIndex;
      const cells = row.children;

      // Get the columnIndex and adjust for any colspans in this row.
      Array.from(cells).forEach((tableCell, cellIndex) => {
        if (cellIndex < index && tableCell.colSpan && tableCell.colSpan > 1) {
          index -= tableCell.colSpan - 1;
        }
      });

      if (index > 0) {
        const cell = cells[index - 1];
        if (cell) {
          if (cell.colSpan > 1) {
            cell.classList.add('tabledrag-has-colspan');
          } else {
            cell.classList.add('tabledrag-hide');
          }
        }
      }
    }

    /**
     * Hide or display weight columns. Triggers an event on change.
     *
     * @fires event:columnschange
     *
     * @param {bool} displayWeight
     *   'true' will show weight columns.
     */
    displayColumns(displayWeight) {
      if (displayWeight) {
        this.constructor.showColumns();
      }
      // Default action is to hide columns.
      else {
        this.constructor.hideColumns();
      }

      this.toggleWeightButton.innerHTML = Drupal.theme(
        'toggleButtonContent',
        displayWeight,
      );

      // Trigger an event to allow other scripts to react to this display change.
      // Force the extra parameter as a bool.
      $('table')
        .findOnce('tabledrag')
        .trigger('columnschange', !!displayWeight);
    }

    /**
     * Toggle the weight column depending on 'showWeight' value.
     *
     * Store only default override.
     */
    toggleColumns() {
      showWeight = !showWeight;
      this.displayColumns(showWeight);
      if (showWeight) {
        // Save default override.
        localStorage.setItem('Drupal.tableDrag.showWeight', showWeight);
      } else {
        // Reset the value to its default.
        localStorage.removeItem('Drupal.tableDrag.showWeight');
      }
    }

    /**
     * Hide the columns containing weight/parent form elements.
     *
     * Undo showColumns().
     */
    static hideColumns() {
      document
        .querySelectorAll('[data-drupal-tabledrag-table]')
        .forEach((table) => {
          table.querySelectorAll('.tabledrag-hide').forEach((tabledragHide) => {
            tabledragHide.style.display = 'none';
          });
          table
            .querySelectorAll('.tabledrag-handle')
            .forEach((tabledragHandle) => {
              tabledragHandle.style.display = '';
            });
          table
            .querySelectorAll('.tabledrag-has-colspan')
            .forEach((tabledragHasColspan) => {
              const colspan = tabledragHasColspan.getAttribute('colspan');
              tabledragHasColspan.setAttribute('colspan', colspan - 1);
            });
        });
    }

    /**
     * Show the columns containing weight/parent form elements.
     *
     * Undo hideColumns().
     */
    static showColumns() {
      document
        .querySelectorAll('[data-drupal-tabledrag-table]')
        .forEach((table) => {
          table.querySelectorAll('.tabledrag-hide').forEach((tabledragHide) => {
            tabledragHide.style.display = '';
          });
          table
            .querySelectorAll('.tabledrag-handle')
            .forEach((tabledragHandle) => {
              tabledragHandle.style.display = 'none';
            });
          table
            .querySelectorAll('.tabledrag-has-colspan')
            .forEach((tabledragHasColspan) => {
              const colspan = tabledragHasColspan.getAttribute('colspan');
              tabledragHasColspan.setAttribute('colspan', colspan + 1);
            });
        });
    }

    /**
     * Find the target used within a particular row and group.
     *
     * @param {string} group
     *   Group selector.
     * @param {HTMLElement} row
     *   The row HTML element.
     *
     * @return {object}
     *   The table row settings.
     */
    rowSettings(group, row) {
      const field = row.querySelector(`.${group}`);
      const tableSettingsGroup = this.tableSettings[group];
      return Object.keys(tableSettingsGroup)
        .map((delta) => {
          const targetClass = tableSettingsGroup[delta].target;
          let rowSettings;
          if (field.matches(`.${targetClass}`)) {
            // Return a copy of the row settings.
            rowSettings = {};
            Object.keys(tableSettingsGroup[delta]).forEach((n) => {
              rowSettings[n] = tableSettingsGroup[delta][n];
            });
          }
          return rowSettings;
        })
        .filter((rowSetting) => rowSetting)[0];
    }

    /**
     * Take an item and add event handlers to make it become draggable.
     *
     * @param {HTMLElement} item
     *   The item to add event handlers to.
     */
    makeDraggable(row) {
      row.querySelectorAll('td:first-of-type a').forEach((link) => {
        link.classList.add('menu-item__link');
      });

      const handle = this.constructor.stringToElement(
        Drupal.theme('tableDragHandle'),
      );

      const indentations = row.querySelectorAll(
        'td:first-of-type .js-indentation',
      );
      const indentationLast =
        indentations.length > 0 ? indentations[indentations.length - 1] : null;
      if (indentationLast) {
        indentationLast.after(handle);
        this.indentCount = Math.max(indentations.length, this.indentCount);
      } else {
        row.querySelector('td').prepend(handle);
      }

      $(handle).on('mousedown touchstart pointerdown', (event) => {
        event.preventDefault();
        const eventToSend =
          event.originalEvent.type === 'touchstart'
            ? event.originalEvent.touches[0]
            : event;
        this.dragStart(eventToSend, row);
      });

      // Prevent the anchor tag from jumping us to the top of the page.
      $(handle).on('click', (e) => {
        e.preventDefault();
      });

      // Set blur cleanup when a handle is focused.
      $(handle).on('focus', () => {
        this.safeBlur = true;
      });

      // On blur, fire the same function as a touchend/mouseup. This is used to
      // update values after a row has been moved through the keyboard support.
      $(handle).on('blur', (event) => {
        if (this.rowObject && this.safeBlur) {
          this.dropRow(event);
        }
      });

      // Add arrow-key support to the handle.
      $(handle).on('keydown', (event) => {
        // If a rowObject doesn't yet exist and this isn't the tab key.
        if (event.keyCode !== 9 && !this.rowObject) {
          this.rowObject = this.row(
            row,
            'keyboard',
            this.indentEnabled,
            this.maxDepth,
            true,
          );
        }

        let keyChange = false;
        let groupHeight;

        /* eslint-disable default-case */
        switch (event.keyCode) {
          // Left arrow.
          case 37:
            keyChange = true;
            this.rowObject.indent(-1 * this.rtl);
            break;

          // Up arrow.
          case 38: {
            let previousRow = this.constructor.previous(
              this.rowObject.element,
              'tr',
            );
            while (previousRow && this.constructor.isHidden(previousRow)) {
              previousRow = this.constructor.previous(previousRow, 'tr');
            }
            if (previousRow) {
              // Do not allow the onBlur cleanup.
              this.safeBlur = false;
              this.rowObject.direction = 'up';
              keyChange = true;

              if (row.matches('.tabledrag-root')) {
                // Swap with the previous top-level row.
                groupHeight = 0;
                while (
                  previousRow &&
                  previousRow.querySelectorAll('.js-indentation').length
                ) {
                  previousRow = this.constructor.previous(previousRow, 'tr');
                  groupHeight += this.constructor.isHidden(previousRow)
                    ? 0
                    : previousRow.offsetHeight;
                }
                if (previousRow) {
                  this.rowObject.swap('beforebegin', previousRow);
                  // No need to check for indentation, 0 is the only valid one.
                  window.scrollBy(0, -groupHeight);
                }
              } else if (
                this.table.tBodies[0].rows[0] !== previousRow ||
                previousRow.matches('.draggable')
              ) {
                // Swap with the previous row (unless previous row is the first
                // one and undraggable).
                this.rowObject.swap('beforebegin', previousRow);
                this.rowObject.interval = null;
                this.rowObject.indent(0);
                window.scrollBy(0, -parseInt(row.offsetHeight, 10));
              }
              // Regain focus after the DOM manipulation.
              handle.focus();
            }
            break;
          }
          // Right arrow.
          case 39:
            keyChange = true;
            this.rowObject.indent(this.rtl);
            break;

          // Down arrow.
          case 40: {
            let nextRow = this.constructor.next(
              this.rowObject.group[this.rowObject.group.length - 1],
              'tr',
            );

            while (nextRow && this.constructor.isHidden(nextRow)) {
              nextRow = this.constructor.next(nextRow);
            }
            if (nextRow) {
              // Do not allow the onBlur cleanup.
              this.safeBlur = false;
              this.rowObject.direction = 'down';
              keyChange = true;

              if (row.matches('.tabledrag-root')) {
                // Swap with the next group (necessarily a top-level one).
                groupHeight = 0;
                const nextGroup = this.row(
                  nextRow,
                  'keyboard',
                  this.indentEnabled,
                  this.maxDepth,
                  false,
                );
                if (nextGroup) {
                  nextGroup.group.forEach((groupRow) => {
                    groupHeight += this.constructor.isHidden(groupRow)
                      ? 0
                      : this.offsetHeight;
                  });

                  const nextGroupRow =
                    nextGroup.group[nextGroup.group.length - 1];
                  this.rowObject.swap('afterend', nextGroupRow);
                  // No need to check for indentation, 0 is the only valid one.
                  window.scrollBy(0, parseInt(groupHeight, 10));
                }
              } else {
                // Swap with the next row.
                this.rowObject.swap('afterend', nextRow);
                this.rowObject.interval = null;
                this.rowObject.indent(0);
                window.scrollBy(0, parseInt(row.offsetHeight, 10));
              }
              // Regain focus after the DOM manipulation.
              handle.focus();
            }
            break;
          }
        }

        /* eslint-enable no-fallthrough */

        if (this.rowObject && this.rowObject.changed === true) {
          row.classList.add('drag');
          if (this.oldRowElement) {
            this.oldRowElement.classList.remove('drag-previous');
          }
          this.oldRowElement = row;
          if (this.striping === true) {
            this.restripeTable();
          }
          this.onDrag();
        }

        // Returning false if we have an arrow key to prevent scrolling.
        if (keyChange) {
          return false;
        }
      });
    }

    /**
     * Pointer event initiator, creates drag object and information.
     *
     * @param {jQuery.Event} event
     *   The event object that trigger the drag.
     * @param {HTMLElement} row
     *   The row being dragged.
     */
    dragStart(event, row) {
      this.dragObject = {};
      this.dragObject.initOffset = this.getPointerOffset(row, event);
      this.dragObject.initPointerCoords = this.constructor.pointerCoords(event);
      if (this.indentEnabled) {
        this.dragObject.indentPointerPos = this.dragObject.initPointerCoords;
      }
      if (this.rowObject) {
        const handle = this.rowObject.element.querySelector(
          'a.tabledrag-handle',
        );
        if (handle) {
          handle.blur();
        }
      }

      this.rowObject = this.row(
        row,
        'pointer',
        this.indentEnabled,
        this.maxDepth,
        true,
      );

      this.table.topY =
        this.table.getBoundingClientRect().top +
        this.table.ownerDocument.defaultView.pageYOffset;
      // $(this.table).offset().top;
      this.table.bottomY = this.table.topY + this.table.offsetHeight;

      // Add classes to the handle and row.
      row.classList.add('drag');

      // Set the document to use the move cursor during drag.
      document.body.classList.add('drag');
      if (this.oldRowElement) {
        this.oldRowElement.classList.remove('drag-previous');
      }

      // Set the initial y coordinate so the direction can be calculated in
      // dragRow().
      this.oldY = this.constructor.pointerCoords(event).y;
    }

    /**
     * Pointer movement handler, bound to document.
     *
     * @param {jQuery.Event} event
     *   The pointer event.
     *
     * @return {bool|undefined}
     *   Undefined if no dragObject is defined, false otherwise.
     */
    dragRow(event) {
      if (this.dragObject) {
        this.currentPointerCoords = this.constructor.pointerCoords(event);
        const y = this.currentPointerCoords.y - this.dragObject.initOffset.y;
        const x = this.currentPointerCoords.x - this.dragObject.initOffset.x;

        // Check for row swapping and vertical scrolling.
        if (y !== this.oldY) {
          this.rowObject.direction = y > this.oldY ? 'down' : 'up';
          // Update the old value.
          this.oldY = y;
          // Check if the window should be scrolled (and how fast).
          const scrollAmount = this.checkScroll(this.currentPointerCoords.y);
          // Stop any current scrolling.
          clearInterval(this.scrollInterval);
          // Continue scrolling if the mouse has moved in the scroll direction.
          if (
            (scrollAmount > 0 && this.rowObject.direction === 'down') ||
            (scrollAmount < 0 && this.rowObject.direction === 'up')
          ) {
            this.setScroll(scrollAmount);
          }

          // If we have a valid target, perform the swap and restripe the table.
          const dropTargetRow = this.findDropTargetRow(x, y);
          if (dropTargetRow) {
            if (this.rowObject.direction === 'down') {
              this.rowObject.swap('afterend', dropTargetRow);
            } else {
              this.rowObject.swap('beforebegin', dropTargetRow);
            }
            if (this.striping === true) {
              this.restripeTable();
            }
          }
        }

        // Similar to row swapping, handle indentations.
        if (this.indentEnabled) {
          const xDiff =
            this.currentPointerCoords.x - this.dragObject.indentPointerPos.x;
          // Set the number of indentations the pointer has been moved left or
          // right.
          const indentDiff = Math.round(xDiff / this.indentAmount);
          // Indent the row with our estimated diff, which may be further
          // restricted according to the rows around this row.
          const indentChange = this.rowObject.indent(indentDiff);
          // Update table and pointer indentations.
          this.dragObject.indentPointerPos.x +=
            this.indentAmount * indentChange * this.rtl;
          this.indentCount = Math.max(this.indentCount, this.rowObject.indents);
        }

        return false;
      }
    }

    /**
     * Pointerup behavior.
     *
     * @param {jQuery.Event} event
     *   The pointer event.
     */
    dropRow(event) {
      // Drop row functionality.
      if (this.rowObject !== null) {
        // The row is already in the right place so we just release it.
        if (this.rowObject.changed === true) {
          // Update the fields in the dropped row.
          this.updateFields(this.rowObject.element);

          // If a setting exists for affecting the entire group, update all the
          // fields in the entire dragged group.
          Object.keys(this.tableSettings || {}).forEach((group) => {
            const rowSettings = this.rowSettings(group, this.rowObject.element);
            if (rowSettings.relationship === 'group') {
              Object.keys(this.rowObject.children || {}).forEach((n) => {
                this.updateField(this.rowObject.children[n], group);
              });
            }
          });

          this.rowObject.markChanged();
          if (this.changed === false) {
            const tableDragChangedWarning = this.constructor.stringToElement(
              Drupal.theme('tableDragChangedWarning'),
            );

            if (tableDragChangedWarning) {
              // @todo jquery, this used jQuery fadeIn(), right now it just pops in.
              this.table.parentNode.insertBefore(
                tableDragChangedWarning,
                this.table,
              );
            }

            this.changed = true;
          }
        }

        if (this.indentEnabled) {
          this.rowObject.removeIndentClasses();
        }
        if (this.oldRowElement) {
          this.oldRowElement.classList.remove('drag-previous');
        }
        this.rowObject.element.classList.remove('drag');
        this.rowObject.element.classList.add('drag-previous');
        this.oldRowElement = this.rowObject.element;
        this.onDrop();
        this.rowObject = null;
      }

      // Functionality specific only to pointerup events.
      if (this.dragObject !== null) {
        this.dragObject = null;
        document.body.classList.remove('drag');
        clearInterval(this.scrollInterval);
      }
    }

    /**
     * Get the coordinates from the event (allowing for browser differences).
     *
     * @param {jQuery.Event} event
     *   The pointer event.
     *
     * @return {object}
     *   An object with `x` and `y` keys indicating the position.
     */
    static pointerCoords(event) {
      if (event.pageX || event.pageY) {
        return { x: event.pageX, y: event.pageY };
      }
      return {
        x: event.clientX + document.body.scrollLeft - document.body.clientLeft,
        y: event.clientY + document.body.scrollTop - document.body.clientTop,
      };
    }

    /**
     * Get the event offset from the target row element.
     *
     * Given a target element and a pointer event, get the event offset from that
     * element. To do this we need the element's position and the target position.
     *
     * @param {HTMLElement} row
     *   The target row HTML element.
     * @param {jQuery.Event} event
     *   The pointer event.
     *
     * @return {object}
     *   An object with `x` and `y` keys indicating the position.
     */
    getPointerOffset(row, event) {
      const rowRect = row.getBoundingClientRect();
      const pointerPos = this.constructor.pointerCoords(event);

      return {
        x:
          pointerPos.x -
          (rowRect.x + row.ownerDocument.defaultView.pageXOffset),
        y:
          pointerPos.y -
          (rowRect.y + row.ownerDocument.defaultView.pageYOffset),
      };
    }

    /**
     * Find the row the mouse is currently over.
     *
     * This row is then taken and swapped with the one being dragged.
     *
     * @param {number} x
     *   The x coordinate of the mouse on the page (not the screen).
     * @param {number} y
     *   The y coordinate of the mouse on the page (not the screen).
     *
     * @return {*}
     *   The drop target row, if found.
     */
    findDropTargetRow(x, y) {
      const rows = [].slice
        .call(this.table.tBodies[0].rows)
        .filter((row) => !this.constructor.isHidden(row));
      for (let n = 0; n < rows.length; n++) {
        let row = rows[n];
        const rowY =
          row.getBoundingClientRect().top +
          row.ownerDocument.defaultView.pageYOffset;
        let rowHeight;

        // Because Safari does not report offsetHeight on table rows, but does on
        // table cells, grab the firstChild of the row and use that instead.
        // http://jacob.peargrove.com/blog/2006/technical/table-row-offsettop-bug-in-safari.
        if (row.offsetHeight === 0) {
          rowHeight = parseInt(row.firstChild.offsetHeight, 10) / 2;
        }
        // Other browsers.
        else {
          rowHeight = parseInt(row.offsetHeight, 10) / 2;
        }
        // Because we always insert before, we need to offset the height a bit.
        if (y > rowY - rowHeight && y < rowY + rowHeight) {
          if (this.indentEnabled) {
            // Check that this row is not a child of the row being dragged.
            if (
              Object.keys(this.rowObject.group).some(
                (o) => this.rowObject.group[o] === row,
              )
            ) {
              return null;
            }
          }
          // Do not allow a row to be swapped with itself.
          else if (row === this.rowObject.element) {
            return null;
          }

          // Check that swapping with this row is allowed.
          if (!this.rowObject.isValidSwap(row)) {
            return null;
          }

          // We may have found the row the mouse just passed over, but it doesn't
          // take into account hidden rows. Skip backwards until we find a
          // draggable row.
          while (
            this.constructor.isHidden(row) &&
            this.constructor.isHidden(this.constructor.previous(row, 'tr'))
          ) {
            row = this.constructor.previous(row, 'tr:first-of-type');
          }

          return row;
        }
      }
      return null;
    }

    /**
     * After the row is dropped, update the table fields.
     *
     * @param {HTMLElement} changedRow
     *   DOM object for the row that was just dropped.
     */
    updateFields(changedRow) {
      Object.keys(this.tableSettings || {}).forEach((group) => {
        // Each group may have a different setting for relationship, so we find
        // the source rows for each separately.
        this.updateField(changedRow, group);
      });
    }

    /**
     * After the row is dropped, update a single table field.
     *
     * @param {HTMLElement} changedRow
     *   DOM object for the row that was just dropped.
     * @param {string} group
     *   The settings group on which field updates will occur.
     */
    updateField(changedRow, group) {
      let rowSettings = this.rowSettings(group, changedRow);
      let sourceRow;
      let previousRow;
      let useSibling;
      // Set the row as its own target.
      if (
        rowSettings.relationship === 'self' ||
        rowSettings.relationship === 'group'
      ) {
        sourceRow = changedRow;
      }
      // Siblings are easy, check previous and next rows.
      else if (rowSettings.relationship === 'sibling') {
        previousRow = this.constructor.previous(changedRow, 'tr:first-of-type');
        const nextRow = this.constructor.next(changedRow, 'tr:first-of-type');
        sourceRow = changedRow;
        if (
          previousRow &&
          previousRow.matches('.draggable') &&
          previousRow.querySelectorAll(`.${group}`).length
        ) {
          if (this.indentEnabled) {
            if (
              previousRow.querySelectorAll('.js-indentations').length ===
              changedRow.querySelectorAll('.js-indentations').length
            ) {
              sourceRow = previousRow;
            }
          } else {
            sourceRow = previousRow;
          }
        } else if (
          nextRow &&
          nextRow.matches('.draggable') &&
          nextRow.querySelectorAll(`.${group}`).length
        ) {
          if (this.indentEnabled) {
            if (
              nextRow.querySelectorAll('.js-indentations').length ===
              changedRow.querySelectorAll('.js-indentations').length
            ) {
              sourceRow = nextRow;
            }
          } else {
            sourceRow = nextRow;
          }
        }
      }

      // Parents, look up the tree until we find a field not in this group.
      // Go up as many parents as indentations in the changed row.
      else if (rowSettings.relationship === 'parent') {
        previousRow = this.constructor.previous(changedRow, 'tr');
        while (
          previousRow &&
          previousRow.querySelectorAll('.js-indentation').length >=
            this.rowObject.indents
        ) {
          previousRow = this.constructor.previous(previousRow, 'tr');
        }
        // If we found a row.
        if (previousRow) {
          sourceRow = previousRow;
        }
        // Otherwise we went all the way to the left of the table without finding
        // a parent, meaning this item has been placed at the root level.
        else {
          // Use the first row in the table as source, because it's guaranteed to
          // be at the root level. Find the first item, then compare this row
          // against it as a sibling.
          sourceRow = this.table.querySelector('tr.draggable');
          if (
            sourceRow === this.rowObject.element &&
            this.table.querySelectorAll('tr.draggable').length > 1
          ) {
            sourceRow = this.constructor.next(
              this.rowObject.group[this.rowObject.group.length - 1],
              'tr.draggable',
            );
          }
          useSibling = true;
        }
      }

      // Because we may have moved the row from one category to another,
      // take a look at our sibling and borrow its sources and targets.
      this.constructor.copyDragClasses(sourceRow, changedRow, group);
      rowSettings = this.rowSettings(group, changedRow);

      // In the case that we're looking for a parent, but the row is at the top
      // of the tree, copy our sibling's values.
      if (useSibling) {
        rowSettings.relationship = 'sibling';
        rowSettings.source = rowSettings.target;
      }

      const targetClass = `.${rowSettings.target}`;
      const targetElement = changedRow.querySelector(targetClass);

      // Check if a target element exists in this row.
      if (targetElement) {
        const sourceClass = `.${rowSettings.source}`;
        const sourceElement = sourceRow.querySelector(sourceClass);

        switch (rowSettings.action) {
          case 'depth':
            // Get the depth of the target row.
            targetElement.value = sourceElement
              .closest('tr')
              .querySelectorAll('.js-indentation').length;
            break;

          case 'match':
            // Update the value.
            targetElement.value = sourceElement.value;
            break;

          case 'order': {
            const siblings = this.rowObject.findSiblings(rowSettings);
            if (targetElement.matches('select')) {
              // Get a list of acceptable values.
              const values = [];
              targetElement.querySelectorAll('option').forEach((option) => {
                values.push(option.value);
              });
              const maxVal = values[values.length - 1];
              siblings.forEach((sibling) => {
                sibling.querySelectorAll(targetClass).forEach((target) => {
                  // If there are more items than possible values, assign the
                  // maximum value to the row.
                  if (values.length > 0) {
                    target.value = values.shift();
                  } else {
                    target.value = maxVal;
                  }
                });
              });
            } else {
              // Assume a numeric input field.
              let weight =
                parseInt(siblings[0].querySelector(targetClass).value, 10) || 0;
              siblings.forEach((sibling) => {
                sibling.querySelectorAll(targetClass).forEach((input) => {
                  input.value = weight;
                  weight += 1;
                });
              });
            }
            break;
          }
        }
      }
    }

    /**
     * Copy all tableDrag related classes from one row to another.
     *
     * Copy all special tableDrag classes from one row's form elements to a
     * different one, removing any special classes that the destination row
     * may have had.
     *
     * @param {HTMLElement} sourceRow
     *   The element for the source row.
     * @param {HTMLElement} targetRow
     *   The element for the target row.
     * @param {string} group
     *   The group selector.
     */
    static copyDragClasses(sourceRow, targetRow, group) {
      const sourceElement = sourceRow
        ? sourceRow.querySelector(`.${group}`)
        : null;
      const targetElement = targetRow
        ? targetRow.querySelector(`.${group}`)
        : null;
      if (sourceElement && targetElement) {
        targetElement.className = sourceElement.className;
      }
    }

    /**
     * Check the suggested scroll of the table.
     *
     * @param {number} cursorY
     *   The Y position of the cursor.
     *
     * @return {number}
     *   The suggested scroll.
     */
    checkScroll(cursorY) {
      const de = document.documentElement;
      const b = document.body;

      const windowHeight =
        window.innerHeight ||
        (de.clientHeight && de.clientWidth !== 0
          ? de.clientHeight
          : b.offsetHeight);
      this.windowHeight = windowHeight;
      let scrollY;
      if (document.all) {
        scrollY = !de.scrollTop ? b.scrollTop : de.scrollTop;
      } else {
        scrollY = window.pageYOffset ? window.pageYOffset : window.scrollY;
      }
      this.scrollY = scrollY;
      const { trigger } = this.scrollSettings;
      let delta = 0;

      // Return a scroll speed relative to the edge of the screen.
      if (cursorY - scrollY > windowHeight - trigger) {
        delta = trigger / (windowHeight + scrollY - cursorY);
        delta = delta > 0 && delta < trigger ? delta : trigger;
        return delta * this.scrollSettings.amount;
      }
      if (cursorY - scrollY < trigger) {
        delta = trigger / (cursorY - scrollY);
        delta = delta > 0 && delta < trigger ? delta : trigger;
        return -delta * this.scrollSettings.amount;
      }
    }

    /**
     * Set the scroll for the table.
     *
     * @param {number} scrollAmount
     *   The amount of scroll to apply to the window.
     */
    setScroll(scrollAmount) {
      this.scrollInterval = setInterval(() => {
        // Update the scroll values stored in the object.
        this.checkScroll(this.currentPointerCoords.y);
        const aboveTable = this.scrollY > this.table.topY;
        const belowTable =
          this.scrollY + this.windowHeight < this.table.bottomY;
        if (
          (scrollAmount > 0 && belowTable) ||
          (scrollAmount < 0 && aboveTable)
        ) {
          window.scrollBy(0, scrollAmount);
        }
      }, this.scrollSettings.interval);
    }

    /**
     * Command to restripe table properly.
     */
    restripeTable() {
      // :even and :odd are reversed because jQuery counts from 0 and
      // we count from 1, so we're out of sync.
      // Match immediate children of the parent element to allow nesting.
      // @todo jquery :scope unsupported in IE11 https://drupal.org/node/3176438
      const draggableRows = this.table.querySelectorAll(
        ':scope > tbody > tr.draggable, :scope > tr.draggable',
      );

      draggableRows.forEach((row) => {
        let i = 0;
        let previousSibling = row.previousElementSibling;
        while (previousSibling) {
          if (this.constructor.isVisible(previousSibling)) {
            i += 1;
          }

          previousSibling = previousSibling.previousElementSibling;
        }
        // @todo jquery the 2nd arg for toggle() will not work in IE11
        // without the polyfill in https://drupal.org/node/3176423
        const isEven = i % 2 === 0;
        row.classList.toggle('even', isEven);
        row.classList.toggle('odd', !isEven);
      });
    }

    /**
     * Stub function. Allows a custom handler when a row begins dragging.
     *
     * @return {null}
     *   Returns null when the stub function is used.
     */
    // eslint-disable-next-line class-methods-use-this
    onDrag() {
      return null;
    }

    /**
     * Stub function. Allows a custom handler when a row is dropped.
     *
     * @return {null}
     *   Returns null when the stub function is used.
     */
    // eslint-disable-next-line class-methods-use-this
    onDrop() {
      return null;
    }

    /**
     * Constructors a new object to manipulate a table row.
     *
     * @param {HTMLElement} tableRow
     *   The DOM element for the table row we will be manipulating.
     * @param {string} method
     *   The method in which this row is being moved. Either 'keyboard' or
     *   'mouse'.
     * @param {bool} indentEnabled
     *   Whether the containing table uses indentations. Used for optimizations.
     * @param {number} maxDepth
     *   The maximum amount of indentations this row may contain.
     * @param {bool} addClasses
     *   Whether we want to add classes to this row to indicate child
     *   relationships.
     *
     * @return {object} a row object.
     */
    row(tableRow, method, indentEnabled, maxDepth, addClasses) {
      const rowObject = {
        element: tableRow,
        method,
        group: [tableRow],
        groupDepth: tableRow.querySelectorAll('.js-indentation').length,
        changed: false,
        // @todo jquery closest() polyfill for IE11
        table: tableRow.closest('table'),
        indentEnabled,
        maxDepth,
        direction: '',
        addClasses,
        swap: this.swap,
        indent: this.indent,
        onSwap: this.onSwap,
        validIndentInterval: this.validIndentInterval,
        onIndent: this.onIndent,
        removeIndentClasses: this.removeIndentClasses,
        findSiblings: this.findSiblings,
        findChildren: this.findChildren,
        isValidSwap: this.isValidSwap,
        markChanged: this.markChanged,
      };

      if (indentEnabled) {
        rowObject.indents = rowObject.groupDepth;
        rowObject.children = rowObject.findChildren();
        rowObject.group = [...rowObject.group, ...rowObject.children];
        // Find the depth of this entire group.
        for (let n = 0; n < rowObject.group.length; n++) {
          const indentationLength =
            typeof rowObject.group[n] !== 'undefined'
              ? rowObject.group[n].querySelectorAll('.js-indentation').length
              : 0;
          rowObject.groupDepth = Math.max(
            indentationLength,
            rowObject.groupDepth,
          );
        }
      }

      return rowObject;
    }

    /**
     * Find all children of rowObject by indentation.
     *
     * @return {Array}
     *   An array of children of the row.
     */
    findChildren() {
      const parentIndentation = this.indents;
      let currentRow = Drupal.TableDrag.next(this.element, 'tr.draggable');
      const rows = [];
      let child = 0;

      function rowIndentation(el, indentNum) {
        if (child === 1 && indentNum === parentIndentation) {
          el.classList.add('tree-child-first');
        }
        if (indentNum === parentIndentation) {
          el.classList.add('tree-child');
        } else if (indentNum > parentIndentation) {
          el.classList.add('tree-child-horizontal');
        }
      }

      while (currentRow) {
        // A greater indentation indicates this is a child.
        if (
          currentRow.querySelectorAll('.js-indentation').length >
          parentIndentation
        ) {
          child += 1;
          rows.push(currentRow);
          if (this.addClasses) {
            currentRow
              .querySelectorAll('.js-indentation')
              .forEach(rowIndentation);
          }
        } else {
          break;
        }
        currentRow = Drupal.TableDrag.next(currentRow, 'tr.draggable');
      }

      if (this.addClasses && rows.length) {
        rows[rows.length - 1]
          .querySelector(`.js-indentation:nth-child(${parentIndentation + 1})`)
          .classList.add('tree-child-last');
      }

      return rows;
    }

    /**
     * Ensure that two rows are allowed to be swapped.
     *
     * @param {HTMLElement} row
     *   DOM object for the row being considered for swapping.
     *
     * @return {boolean}
     *   Whether the swap is a valid swap or not.
     */
    isValidSwap(row) {
      if (this.indentEnabled) {
        let prevRow;
        let nextRow;
        if (this.direction === 'down') {
          prevRow = row;
          nextRow = Drupal.TableDrag.next(row, 'tr');
        } else {
          prevRow = Drupal.TableDrag.previous(row, 'tr');
          nextRow = row;
        }
        this.interval = this.validIndentInterval(prevRow, nextRow);

        // We have an invalid swap if the valid indentations interval is empty.
        if (this.interval.min > this.interval.max) {
          return false;
        }
      }

      // Do not let an un-draggable first row have anything put before it.
      if (!row.matches('.draggable') && this.table.tBodies[0].rows[0] === row) {
        return false;
      }

      return true;
    }

    /**
     * Perform the swap between two rows.
     *
     * @param {InsertPosition} position
     *   Whether the swap will occur before or after the given row.
     * @param {HTMLElement} row
     *   DOM element what will be swapped with the row group.
     */
    swap(position, row) {
      this.group.forEach((rowInGroup) => {
        Drupal.detachBehaviors(rowInGroup, drupalSettings, 'move');
      });

      row.insertAdjacentElement(position, this.group[0]);

      // Move all group children as well.
      this.group.forEach((rowInGroup, index) => {
        if (index !== 0) {
          this.group[index - 1].insertAdjacentElement('afterend', rowInGroup);
        }
        Drupal.attachBehaviors(rowInGroup, drupalSettings);
      });
      this.changed = true;
      this.onSwap(row);
    }

    /**
     * Determine the valid indentations interval for the row at a given position.
     *
     * @param {?HTMLElement} prevRow
     *   DOM object for the row before the tested position
     *   (or null for first position in the table).
     * @param {?HTMLElement} nextRow
     *   DOM object for the row after the tested position
     *   (or null for last position in the table).
     *
     * @return {object}
     *   An object with the keys `min` and `max` to indicate the valid indent
     *   interval.
     */
    validIndentInterval(prevRow, nextRow) {
      let maxIndent;

      // Minimum indentation:
      // Do not orphan the next row.
      const minIndent = nextRow
        ? nextRow.querySelectorAll('.js-indentation').length
        : 0;

      // Maximum indentation:
      if (
        !prevRow ||
        !prevRow.matches('.draggable') ||
        this.element.matches('.tabledrag-root')
      ) {
        // Do not indent:
        // - the first row in the table,
        // - rows dragged below a non-draggable row,
        // - 'root' rows.
        maxIndent = 0;
      } else {
        // Do not go deeper than as a child of the previous row.
        maxIndent =
          prevRow.querySelectorAll('.js-indentation').length +
          (prevRow.matches('.tabledrag-leaf') ? 0 : 1);
        // Limit by the maximum allowed depth for the table.
        if (this.maxDepth) {
          maxIndent = Math.min(
            maxIndent,
            this.maxDepth - (this.groupDepth - this.indents),
          );
        }
      }

      return { min: minIndent, max: maxIndent };
    }

    /**
     * Indent a row within the legal bounds of the table.
     *
     * @param {number} indentDiff
     *   The number of additional indentations proposed for the row (can be
     *   positive or negative). This number will be adjusted to nearest valid
     *   indentation level for the row.
     *
     * @return {number}
     *   The number of indentations applied.
     */
    indent(indentDiff) {
      // Determine the valid indentations interval if not available yet.
      if (!this.interval) {
        const prevRow = Drupal.TableDrag.previous(this.element, 'tr');
        const nextRow = Drupal.TableDrag.next(
          this.group[this.group.length - 1],
          'tr',
        );
        this.interval = this.validIndentInterval(prevRow, nextRow);
      }

      // Adjust to the nearest valid indentation.
      let indent = this.indents + indentDiff;
      indent = Math.max(indent, this.interval.min);
      indent = Math.min(indent, this.interval.max);
      indentDiff = indent - this.indents;

      for (let n = 1; n <= Math.abs(indentDiff); n++) {
        // Add or remove indentations.
        if (indentDiff < 0) {
          this.group.forEach((groupRow) => {
            const firstIndent = groupRow.querySelector(
              '.js-indentation:first-of-type',
            );
            firstIndent.parentNode.removeChild(firstIndent);
          });
          this.indents -= 1;
        } else {
          const newIndent = Drupal.TableDrag.stringToElement(
            Drupal.theme('tableDragIndentation'),
          );
          this.group.forEach((groupRow) => {
            const cellWithIndents = groupRow.querySelector('td:first-of-type');
            cellWithIndents.insertBefore(
              newIndent.cloneNode(true),
              cellWithIndents.firstChild,
            );
          });

          this.indents += 1;
        }
      }
      if (indentDiff) {
        // Update indentation for this row.
        this.changed = true;
        this.groupDepth += indentDiff;
        this.onIndent();
      }

      return indentDiff;
    }

    /**
     * Find all siblings for a row.
     *
     * According to its subgroup or indentation. Note that the passed-in row is
     * included in the list of siblings.
     *
     * @param {object} rowSettings
     *   The field settings we're using to identify what constitutes a sibling.
     *
     * @return {Array}
     *   An array of siblings.
     */
    findSiblings(rowSettings) {
      const siblings = [];
      const directions = ['previousElementSibling', 'nextElementSibling'];
      const rowIndentation = this.indents;
      let checkRowIndentation;
      for (let d = 0; d < directions.length; d++) {
        let checkRow = this.element[directions[d]];
        while (checkRow) {
          // Check that the sibling contains a similar target field.
          if (checkRow.querySelector(`.${rowSettings.target}`)) {
            // Either add immediately if this is a flat table, or check to ensure
            // that this row has the same level of indentation.
            if (this.indentEnabled) {
              checkRowIndentation = checkRow.querySelectorAll('.js-indentation')
                .length;
            }

            if (!this.indentEnabled || checkRowIndentation === rowIndentation) {
              siblings.push(checkRow);
            } else if (checkRowIndentation < rowIndentation) {
              // No need to keep looking for siblings when we get to a parent.
              break;
            }
          } else {
            break;
          }
          checkRow = checkRow[directions[d]];
        }
        // Since siblings are added in reverse order for previous, reverse the
        // completed list of previous siblings. Add the current row and continue.
        if (directions[d] === 'previousElementSibling') {
          siblings.reverse();
          siblings.push(this.element);
        }
      }
      return siblings;
    }

    /**
     * Remove indentation helper classes from the current row group.
     */
    removeIndentClasses() {
      Object.keys(this.children || {}).forEach((n) => {
        if (typeof this.children[n] !== 'undefined') {
          this.children[n]
            .querySelectorAll('.js-indentation')
            .forEach((indentation) => {
              // @todo jquery | IE11 doesn't support multiple args for remove
              // without the polyfill in https://drupal.org/node/3176423
              indentation.classList.remove(
                'tree-child',
                'tree-child-first',
                'tree-child-last',
                'tree-child-horizontal',
              );
            });
        }
      });
    }

    /**
     * Add an asterisk or other marker to the changed row.
     */
    markChanged() {
      const marker = Drupal.TableDrag.stringToElement(
        Drupal.theme('tableDragChangedMarker'),
      );
      const cell = this.element.querySelector('td:first-of-type');
      if (cell.querySelectorAll('abbr.tabledrag-changed').length === 0) {
        cell.append(marker);
      }
    }

    /**
     * Stub function. Allows a custom handler when a row is indented.
     *
     * @return {null}
     *   Returns null when the stub function is used.
     */
    // eslint-disable-next-line class-methods-use-this
    onIndent() {
      return null;
    }

    /**
     * Stub function. Allows a custom handler when a row is swapped.
     *
     * @param {HTMLElement} swappedRow
     *   The element for the swapped row.
     *
     * @return {null}
     *   Returns null when the stub function is used.
     */
    // eslint-disable-next-line class-methods-use-this
    onSwap(swappedRow) {
      return null;
    }
  };

  Drupal.TableDrag.instances = [];
  /**
   * @return {string}
   *  Markup for the marker.
   */
  Drupal.theme.tableDragChangedMarker = () =>
    `<abbr class="warning tabledrag-changed" title="${Drupal.t(
      'Changed',
    )}">*</abbr>`;

  /**
   * @return {string}
   *   Markup for the indentation.
   */
  Drupal.theme.tableDragIndentation = () =>
    '<div class="js-indentation indentation">&nbsp;</div>';

  /**
   * @return {string}
   *   Markup for the warning.
   */
  Drupal.theme.tableDragChangedWarning = () =>
    `<div class="tabledrag-changed-warning messages messages--warning" role="alert">${Drupal.theme(
      'tableDragChangedMarker',
    )} ${Drupal.t('You have unsaved changes.')}</div>`;

  /**
   * The button for toggling table row weight visibility.
   *
   * @return {string}
   *   HTML markup for the weight toggle button and its container.
   */
  Drupal.theme.tableDragToggle = () =>
    `<div class="tabledrag-toggle-weight-wrapper" data-drupal-selector="tabledrag-toggle-weight-wrapper">
            <button type="button" class="link tabledrag-toggle-weight" data-drupal-selector="tabledrag-toggle-weight"></button>
            </div>`;

  /**
   * The contents of the toggle weight button.
   *
   * @param {boolean} show
   *   If the table weights are currently displayed.
   *
   * @return {string}
   *  HTML markup for the weight toggle button content.s
   */
  Drupal.theme.toggleButtonContent = (show) =>
    show ? Drupal.t('Hide row weights') : Drupal.t('Show row weights');

  /**
   * @return {string}
   *   HTML markup for a tableDrag handle.
   */
  Drupal.theme.tableDragHandle = () =>
    `<a href="#" title="${Drupal.t('Drag to re-order')}"
        class="tabledrag-handle"><div class="handle">&nbsp;</div></a>`;
})(jQuery, Drupal, drupalSettings);
