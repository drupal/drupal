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
          Drupal.tableDrag[base] = new Drupal.tableDrag(
            table[0],
            settings.tableDrag[base],
          );
        }
      }

      Object.keys(settings.tableDrag || {}).forEach((base) => {
        initTableDrag($(once('tabledrag', `#${base}`, context)), base);
      });
    },
  };

  /**
   * Provides table and field manipulation.
   *
   * @constructor
   *
   * @param {HTMLElement} table
   *   DOM object for the table to be made draggable.
   * @param {object} tableSettings
   *   Settings for the table added via drupal_add_dragtable().
   */
  Drupal.tableDrag = function (table, tableSettings) {
    const self = this;
    const $table = $(table);

    /**
     * @type {jQuery}
     */
    this.$table = $(table);

    /**
     *
     * @type {HTMLElement}
     */
    this.table = table;

    /**
     * @type {object}
     */
    this.tableSettings = tableSettings;

    /**
     * Used to hold information about a current drag operation.
     *
     * @type {?HTMLElement}
     */
    this.dragObject = null;

    /**
     * Provides operations for row manipulation.
     *
     * @type {?HTMLElement}
     */
    this.rowObject = null;

    /**
     * Remember the previous element.
     *
     * @type {?HTMLElement}
     */
    this.oldRowElement = null;

    /**
     * Used to determine up or down direction from last mouse move.
     *
     * @type {?number}
     */
    this.oldY = null;

    /**
     * Whether anything in the entire table has changed.
     *
     * @type {bool}
     */
    this.changed = false;

    /**
     * Maximum amount of allowed parenting.
     *
     * @type {number}
     */
    this.maxDepth = 0;

    /**
     * Direction of the table.
     *
     * @type {number}
     */
    this.rtl = $(this.table).css('direction') === 'rtl' ? -1 : 1;

    /**
     *
     * @type {bool}
     */
    this.striping = $(this.table).data('striping') === 1;

    /**
     * Configure the scroll settings.
     *
     * @type {object}
     *
     * @prop {number} amount
     * @prop {number} interval
     * @prop {number} trigger
     */
    this.scrollSettings = { amount: 4, interval: 50, trigger: 70 };

    /**
     *
     * @type {?number}
     */
    this.scrollInterval = null;

    /**
     *
     * @type {number}
     */
    this.scrollY = 0;

    /**
     *
     * @type {number}
     */
    this.windowHeight = 0;

    /**
     * @type {?jQuery}
     */
    this.$toggleWeightButton = null;

    /**
     * Check this table's settings for parent relationships.
     *
     * For efficiency, large sections of code can be skipped if we don't need to
     * track horizontal movement and indentations.
     *
     * @type {bool}
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
      const indent = Drupal.theme('tableDragIndentation');
      const testRow = $('<tr></tr>').addClass('draggable').appendTo(table);
      const testCell = $('<td></td>')
        .appendTo(testRow)
        .prepend(indent)
        .prepend(indent);
      const $indentation = testCell.find('.js-indentation');

      /**
       *
       * @type {number}
       */
      this.indentAmount =
        $indentation.get(1).offsetLeft - $indentation.get(0).offsetLeft;
      testRow.remove();
    }

    // Make each applicable row draggable.
    // Match immediate children of the parent element to allow nesting.
    $table.find('> tr.draggable, > tbody > tr.draggable').each(function () {
      self.makeDraggable(this);
    });

    const $toggleWeightWrapper = $(Drupal.theme('tableDragToggle'));
    this.$toggleWeightButton = $toggleWeightWrapper.find(
      '[data-drupal-selector="tabledrag-toggle-weight"]',
    );
    this.$toggleWeightButton.on(
      'click',
      $.proxy(function (e) {
        e.preventDefault();
        this.toggleColumns();
      }, this),
    );
    $table.before($toggleWeightWrapper);

    // Initialize the specified columns (for example, weight or parent columns)
    // to show or hide according to user preference. This aids accessibility
    // so that, e.g., screen reader users can choose to enter weight values and
    // manipulate form elements directly, rather than using drag-and-drop..
    self.initColumns();

    // Add event bindings to the document. The self variable is passed along
    // as event handlers do not have direct access to the tableDrag object.
    $(document).on('touchmove', (event) =>
      self.dragRow(event.originalEvent.touches[0], self),
    );
    $(document).on('touchend', (event) =>
      self.dropRow(event.originalEvent.touches[0], self),
    );
    $(document).on('mousemove pointermove', (event) =>
      self.dragRow(event, self),
    );
    $(document).on('mouseup pointerup', (event) => self.dropRow(event, self));

    // React to localStorage event showing or hiding weight columns.
    $(window).on(
      'storage',
      $.proxy(function (e) {
        // Only react to 'Drupal.tableDrag.showWeight' value change.
        if (e.originalEvent.key === 'Drupal.tableDrag.showWeight') {
          // This was changed in another window, get the new value for this
          // window.
          showWeight = JSON.parse(e.originalEvent.newValue);
          this.displayColumns(showWeight);
        }
      }, this),
    );
  };

  /**
   * Initialize columns containing form elements to be hidden by default.
   *
   * Identify and mark each cell with a CSS class so we can easily toggle
   * show/hide it. Finally, hide columns if user does not have a
   * 'Drupal.tableDrag.showWeight' localStorage value.
   */
  Drupal.tableDrag.prototype.initColumns = function () {
    const $table = this.$table;
    let hidden;
    let cell;
    let columnIndex;
    Object.keys(this.tableSettings || {}).forEach((group) => {
      // Find the first field in this group.
      Object.keys(this.tableSettings[group]).some((tableSetting) => {
        const field = $table
          .find(`.${this.tableSettings[group][tableSetting].target}`)
          .eq(0);
        if (field.length && this.tableSettings[group][tableSetting].hidden) {
          hidden = this.tableSettings[group][tableSetting].hidden;
          cell = field.closest('td');
          return true;
        }
        return false;
      });

      // Mark the column containing this field so it can be hidden.
      if (hidden && cell[0]) {
        // Add 1 to our indexes. The nth-child selector is 1 based, not 0
        // based. Match immediate children of the parent element to allow
        // nesting.
        columnIndex = cell.parent().find('> td').index(cell.get(0)) + 1;
        $table
          .find('> thead > tr, > tbody > tr, > tr')
          .each(this.addColspanClass(columnIndex));
      }
    });
    this.displayColumns(showWeight);
  };

  /**
   * Mark cells that have colspan.
   *
   * In order to adjust the colspan instead of hiding them altogether.
   *
   * @param {number} columnIndex
   *   The column index to add colspan class to.
   *
   * @return {function}
   *   Function to add colspan class.
   */
  Drupal.tableDrag.prototype.addColspanClass = function (columnIndex) {
    return function () {
      // Get the columnIndex and adjust for any colspans in this row.
      const $row = $(this);
      let index = columnIndex;
      const cells = $row.children();
      let cell;
      cells.each(function (n) {
        if (n < index && this.colSpan && this.colSpan > 1) {
          index -= this.colSpan - 1;
        }
      });
      if (index > 0) {
        cell = cells.filter(`:nth-child(${index})`);
        if (cell[0].colSpan && cell[0].colSpan > 1) {
          // If this cell has a colspan, mark it so we can reduce the colspan.
          cell.addClass('tabledrag-has-colspan');
        } else {
          // Mark this cell so we can hide it.
          cell.addClass('tabledrag-hide');
        }
      }
    };
  };

  /**
   * Hide or display weight columns. Triggers an event on change.
   *
   * @fires event:columnschange
   *
   * @param {bool} displayWeight
   *   'true' will show weight columns.
   */
  Drupal.tableDrag.prototype.displayColumns = function (displayWeight) {
    if (displayWeight) {
      this.showColumns();
    }
    // Default action is to hide columns.
    else {
      this.hideColumns();
    }

    this.$toggleWeightButton.html(
      Drupal.theme('toggleButtonContent', displayWeight),
    );

    // Trigger an event to allow other scripts to react to this display change.
    // Force the extra parameter as a bool.
    $(once.filter('tabledrag', 'table')).trigger(
      'columnschange',
      !!displayWeight,
    );
  };

  /**
   * Toggle the weight column depending on 'showWeight' value.
   *
   * Store only default override.
   */
  Drupal.tableDrag.prototype.toggleColumns = function () {
    showWeight = !showWeight;
    this.displayColumns(showWeight);
    if (showWeight) {
      // Save default override.
      localStorage.setItem('Drupal.tableDrag.showWeight', showWeight);
    } else {
      // Reset the value to its default.
      localStorage.removeItem('Drupal.tableDrag.showWeight');
    }
  };

  /**
   * Hide the columns containing weight/parent form elements.
   *
   * Undo showColumns().
   */
  Drupal.tableDrag.prototype.hideColumns = function () {
    const $tables = $(once.filter('tabledrag', 'table'));
    // Hide weight/parent cells and headers.
    $tables.find('.tabledrag-hide').css('display', 'none');
    // Show TableDrag handles.
    $tables.find('.tabledrag-handle').css('display', '');
    // Reduce the colspan of any effected multi-span columns.
    $tables.find('.tabledrag-has-colspan').each(function () {
      this.colSpan -= 1;
    });
  };

  /**
   * Show the columns containing weight/parent form elements.
   *
   * Undo hideColumns().
   */
  Drupal.tableDrag.prototype.showColumns = function () {
    const $tables = $(once.filter('tabledrag', 'table'));
    // Show weight/parent cells and headers.
    $tables.find('.tabledrag-hide').css('display', '');
    // Hide TableDrag handles.
    $tables.find('.tabledrag-handle').css('display', 'none');
    // Increase the colspan for any columns where it was previously reduced.
    $tables.find('.tabledrag-has-colspan').each(function () {
      this.colSpan += 1;
    });
  };

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
  Drupal.tableDrag.prototype.rowSettings = function (group, row) {
    const field = $(row).find(`.${group}`);
    const tableSettingsGroup = this.tableSettings[group];
    return Object.keys(tableSettingsGroup)
      .map((delta) => {
        const targetClass = tableSettingsGroup[delta].target;
        let rowSettings;
        if (field.is(`.${targetClass}`)) {
          // Return a copy of the row settings.
          rowSettings = {};
          Object.keys(tableSettingsGroup[delta]).forEach((n) => {
            rowSettings[n] = tableSettingsGroup[delta][n];
          });
        }
        return rowSettings;
      })
      .filter((rowSetting) => rowSetting)[0];
  };

  /**
   * Take an item and add event handlers to make it become draggable.
   *
   * @param {HTMLElement} item
   *   The item to add event handlers to.
   */
  Drupal.tableDrag.prototype.makeDraggable = function (item) {
    const self = this;
    const $item = $(item);
    // Add a class to the title link.
    $item.find('td:first-of-type').find('a').addClass('menu-item__link');
    // Create the handle.
    const $handle = $(Drupal.theme('tableDragHandle'));
    // Insert the handle after indentations (if any).
    const $indentationLast = $item
      .find('td:first-of-type')
      .find('.js-indentation')
      .eq(-1);
    if ($indentationLast.length) {
      $indentationLast.after($handle);
      // Update the total width of indentation in this entire table.
      self.indentCount = Math.max(
        $item.find('.js-indentation').length,
        self.indentCount,
      );
    } else {
      $item.find('td').eq(0).prepend($handle);
    }

    $handle.on('mousedown touchstart pointerdown', (event) => {
      event.preventDefault();
      if (event.originalEvent.type === 'touchstart') {
        event = event.originalEvent.touches[0];
      }
      self.dragStart(event, self, item);
    });

    // Prevent the anchor tag from jumping us to the top of the page.
    $handle.on('click', (e) => {
      e.preventDefault();
    });

    // Set blur cleanup when a handle is focused.
    $handle.on('focus', () => {
      self.safeBlur = true;
    });

    // On blur, fire the same function as a touchend/mouseup. This is used to
    // update values after a row has been moved through the keyboard support.
    $handle.on('blur', (event) => {
      if (self.rowObject && self.safeBlur) {
        self.dropRow(event, self);
      }
    });

    // Add arrow-key support to the handle.
    $handle.on('keydown', (event) => {
      // If a rowObject doesn't yet exist and this isn't the tab key.
      if (event.keyCode !== 9 && !self.rowObject) {
        self.rowObject = new self.row(
          item,
          'keyboard',
          self.indentEnabled,
          self.maxDepth,
          true,
        );
      }

      let keyChange = false;
      let groupHeight;

      /* eslint-disable no-fallthrough */

      switch (event.keyCode) {
        // Left arrow.
        case 37:
        // Safari left arrow.
        case 63234:
          keyChange = true;
          self.rowObject.indent(-1 * self.rtl);
          break;

        // Up arrow.
        case 38:
        // Safari up arrow.
        case 63232: {
          let $previousRow = $(self.rowObject.element).prev('tr').eq(0);
          let previousRow = $previousRow.get(0);
          while (previousRow && $previousRow.is(':hidden')) {
            $previousRow = $(previousRow).prev('tr').eq(0);
            previousRow = $previousRow.get(0);
          }
          if (previousRow) {
            // Do not allow the onBlur cleanup.
            self.safeBlur = false;
            self.rowObject.direction = 'up';
            keyChange = true;

            if ($(item).is('.tabledrag-root')) {
              // Swap with the previous top-level row.
              groupHeight = 0;
              while (
                previousRow &&
                $previousRow.find('.js-indentation').length
              ) {
                $previousRow = $(previousRow).prev('tr').eq(0);
                previousRow = $previousRow.get(0);
                groupHeight += $previousRow.is(':hidden')
                  ? 0
                  : previousRow.offsetHeight;
              }
              if (previousRow) {
                self.rowObject.swap('before', previousRow);
                // No need to check for indentation, 0 is the only valid one.
                window.scrollBy(0, -groupHeight);
              }
            } else if (
              self.table.tBodies[0].rows[0] !== previousRow ||
              $previousRow.is('.draggable')
            ) {
              // Swap with the previous row (unless previous row is the first
              // one and undraggable).
              self.rowObject.swap('before', previousRow);
              self.rowObject.interval = null;
              self.rowObject.indent(0);
              window.scrollBy(0, -parseInt(item.offsetHeight, 10));
            }
            // Regain focus after the DOM manipulation.
            $handle.trigger('focus');
          }
          break;
        }
        // Right arrow.
        case 39:
        // Safari right arrow.
        case 63235:
          keyChange = true;
          self.rowObject.indent(self.rtl);
          break;

        // Down arrow.
        case 40:
        // Safari down arrow.
        case 63233: {
          let $nextRow = $(self.rowObject.group).eq(-1).next('tr').eq(0);
          let nextRow = $nextRow.get(0);
          while (nextRow && $nextRow.is(':hidden')) {
            $nextRow = $(nextRow).next('tr').eq(0);
            nextRow = $nextRow.get(0);
          }
          if (nextRow) {
            // Do not allow the onBlur cleanup.
            self.safeBlur = false;
            self.rowObject.direction = 'down';
            keyChange = true;

            if ($(item).is('.tabledrag-root')) {
              // Swap with the next group (necessarily a top-level one).
              groupHeight = 0;
              const nextGroup = new self.row(
                nextRow,
                'keyboard',
                self.indentEnabled,
                self.maxDepth,
                false,
              );
              if (nextGroup) {
                $(nextGroup.group).each(function () {
                  groupHeight += $(this).is(':hidden') ? 0 : this.offsetHeight;
                });
                const nextGroupRow = $(nextGroup.group).eq(-1).get(0);
                self.rowObject.swap('after', nextGroupRow);
                // No need to check for indentation, 0 is the only valid one.
                window.scrollBy(0, parseInt(groupHeight, 10));
              }
            } else {
              // Swap with the next row.
              self.rowObject.swap('after', nextRow);
              self.rowObject.interval = null;
              self.rowObject.indent(0);
              window.scrollBy(0, parseInt(item.offsetHeight, 10));
            }
            // Regain focus after the DOM manipulation.
            $handle.trigger('focus');
          }
          break;
        }
      }

      /* eslint-enable no-fallthrough */

      if (self.rowObject && self.rowObject.changed === true) {
        $(item).addClass('drag');
        if (self.oldRowElement) {
          $(self.oldRowElement).removeClass('drag-previous');
        }
        self.oldRowElement = item;
        if (self.striping === true) {
          self.restripeTable();
        }
        self.onDrag();
      }

      // Returning false if we have an arrow key to prevent scrolling.
      if (keyChange) {
        return false;
      }
    });

    // Compatibility addition, return false on keypress to prevent unwanted
    // scrolling. IE and Safari will suppress scrolling on keydown, but all
    // other browsers need to return false on keypress.
    // http://www.quirksmode.org/js/keys.html
    $handle.on('keypress', (event) => {
      /* eslint-disable no-fallthrough */

      switch (event.keyCode) {
        // Left arrow.
        case 37:
        // Up arrow.
        case 38:
        // Right arrow.
        case 39:
        // Down arrow.
        case 40:
          return false;
      }

      /* eslint-enable no-fallthrough */
    });
  };

  /**
   * Pointer event initiator, creates drag object and information.
   *
   * @param {jQuery.Event} event
   *   The event object that trigger the drag.
   * @param {Drupal.tableDrag} self
   *   The drag handle.
   * @param {HTMLElement} item
   *   The item that is being dragged.
   */
  Drupal.tableDrag.prototype.dragStart = function (event, self, item) {
    // Create a new dragObject recording the pointer information.
    self.dragObject = {};
    self.dragObject.initOffset = self.getPointerOffset(item, event);
    self.dragObject.initPointerCoords = self.pointerCoords(event);
    if (self.indentEnabled) {
      self.dragObject.indentPointerPos = self.dragObject.initPointerCoords;
    }

    // If there's a lingering row object from the keyboard, remove its focus.
    if (self.rowObject) {
      $(self.rowObject.element).find('a.tabledrag-handle').trigger('blur');
    }

    // Create a new rowObject for manipulation of this row.
    self.rowObject = new self.row(
      item,
      'pointer',
      self.indentEnabled,
      self.maxDepth,
      true,
    );

    // Save the position of the table.
    self.table.topY = $(self.table).offset().top;
    self.table.bottomY = self.table.topY + self.table.offsetHeight;

    // Add classes to the handle and row.
    $(item).addClass('drag');

    // Set the document to use the move cursor during drag.
    $('body').addClass('drag');
    if (self.oldRowElement) {
      $(self.oldRowElement).removeClass('drag-previous');
    }

    // Set the initial y coordinate so the direction can be calculated in
    // dragRow().
    self.oldY = self.pointerCoords(event).y;
  };

  /**
   * Pointer movement handler, bound to document.
   *
   * @param {jQuery.Event} event
   *   The pointer event.
   * @param {Drupal.tableDrag} self
   *   The tableDrag instance.
   *
   * @return {bool|undefined}
   *   Undefined if no dragObject is defined, false otherwise.
   */
  Drupal.tableDrag.prototype.dragRow = function (event, self) {
    if (self.dragObject) {
      self.currentPointerCoords = self.pointerCoords(event);
      const y = self.currentPointerCoords.y - self.dragObject.initOffset.y;
      const x = self.currentPointerCoords.x - self.dragObject.initOffset.x;

      // Check for row swapping and vertical scrolling.
      if (y !== self.oldY) {
        self.rowObject.direction = y > self.oldY ? 'down' : 'up';
        // Update the old value.
        self.oldY = y;
        // Check if the window should be scrolled (and how fast).
        const scrollAmount = self.checkScroll(self.currentPointerCoords.y);
        // Stop any current scrolling.
        clearInterval(self.scrollInterval);
        // Continue scrolling if the mouse has moved in the scroll direction.
        if (
          (scrollAmount > 0 && self.rowObject.direction === 'down') ||
          (scrollAmount < 0 && self.rowObject.direction === 'up')
        ) {
          self.setScroll(scrollAmount);
        }

        // If we have a valid target, perform the swap and restripe the table.
        const currentRow = self.findDropTargetRow(x, y);
        if (currentRow) {
          if (self.rowObject.direction === 'down') {
            self.rowObject.swap('after', currentRow, self);
          } else {
            self.rowObject.swap('before', currentRow, self);
          }
          if (self.striping === true) {
            self.restripeTable();
          }
        }
      }

      // Similar to row swapping, handle indentations.
      if (self.indentEnabled) {
        const xDiff =
          self.currentPointerCoords.x - self.dragObject.indentPointerPos.x;
        // Set the number of indentations the pointer has been moved left or
        // right.
        const indentDiff = Math.round(xDiff / self.indentAmount);
        // Indent the row with our estimated diff, which may be further
        // restricted according to the rows around this row.
        const indentChange = self.rowObject.indent(indentDiff);
        // Update table and pointer indentations.
        self.dragObject.indentPointerPos.x +=
          self.indentAmount * indentChange * self.rtl;
        self.indentCount = Math.max(self.indentCount, self.rowObject.indents);
      }

      return false;
    }
  };

  /**
   * Pointerup behavior.
   *
   * @param {jQuery.Event} event
   *   The pointer event.
   * @param {Drupal.tableDrag} self
   *   The tableDrag instance.
   */
  Drupal.tableDrag.prototype.dropRow = function (event, self) {
    let droppedRow;
    let $droppedRow;

    // Drop row functionality.
    if (self.rowObject !== null) {
      droppedRow = self.rowObject.element;
      $droppedRow = $(droppedRow);
      // The row is already in the right place so we just release it.
      if (self.rowObject.changed === true) {
        // Update the fields in the dropped row.
        self.updateFields(droppedRow);

        // If a setting exists for affecting the entire group, update all the
        // fields in the entire dragged group.
        Object.keys(self.tableSettings || {}).forEach((group) => {
          const rowSettings = self.rowSettings(group, droppedRow);
          if (rowSettings.relationship === 'group') {
            Object.keys(self.rowObject.children || {}).forEach((n) => {
              self.updateField(self.rowObject.children[n], group);
            });
          }
        });

        self.rowObject.markChanged();
        if (self.changed === false) {
          $(Drupal.theme('tableDragChangedWarning'))
            .insertBefore(self.table)
            .hide()
            .fadeIn('slow');
          self.changed = true;
        }
      }

      if (self.indentEnabled) {
        self.rowObject.removeIndentClasses();
      }
      if (self.oldRowElement) {
        $(self.oldRowElement).removeClass('drag-previous');
      }
      $droppedRow.removeClass('drag').addClass('drag-previous');
      self.oldRowElement = droppedRow;
      self.onDrop();
      self.rowObject = null;
    }

    // Functionality specific only to pointerup events.
    if (self.dragObject !== null) {
      self.dragObject = null;
      $('body').removeClass('drag');
      clearInterval(self.scrollInterval);
    }
  };

  /**
   * Get the coordinates from the event (allowing for browser differences).
   *
   * @param {jQuery.Event} event
   *   The pointer event.
   *
   * @return {object}
   *   An object with `x` and `y` keys indicating the position.
   */
  Drupal.tableDrag.prototype.pointerCoords = function (event) {
    if (event.pageX || event.pageY) {
      return { x: event.pageX, y: event.pageY };
    }
    return {
      x: event.clientX + document.body.scrollLeft - document.body.clientLeft,
      y: event.clientY + document.body.scrollTop - document.body.clientTop,
    };
  };

  /**
   * Get the event offset from the target element.
   *
   * Given a target element and a pointer event, get the event offset from that
   * element. To do this we need the element's position and the target position.
   *
   * @param {HTMLElement} target
   *   The target HTML element.
   * @param {jQuery.Event} event
   *   The pointer event.
   *
   * @return {object}
   *   An object with `x` and `y` keys indicating the position.
   */
  Drupal.tableDrag.prototype.getPointerOffset = function (target, event) {
    const docPos = $(target).offset();
    const pointerPos = this.pointerCoords(event);
    return { x: pointerPos.x - docPos.left, y: pointerPos.y - docPos.top };
  };

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
  Drupal.tableDrag.prototype.findDropTargetRow = function (x, y) {
    const rows = $(this.table.tBodies[0].rows).not(':hidden');
    for (let n = 0; n < rows.length; n++) {
      let row = rows[n];
      let $row = $(row);
      const rowY = $row.offset().top;
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
        while ($row.is(':hidden') && $row.prev('tr').is(':hidden')) {
          $row = $row.prev('tr:first-of-type');
          row = $row.get(0);
        }
        return row;
      }
    }
    return null;
  };

  /**
   * After the row is dropped, update the table fields.
   *
   * @param {HTMLElement} changedRow
   *   DOM object for the row that was just dropped.
   */
  Drupal.tableDrag.prototype.updateFields = function (changedRow) {
    Object.keys(this.tableSettings || {}).forEach((group) => {
      // Each group may have a different setting for relationship, so we find
      // the source rows for each separately.
      this.updateField(changedRow, group);
    });
  };

  /**
   * After the row is dropped, update a single table field.
   *
   * @param {HTMLElement} changedRow
   *   DOM object for the row that was just dropped.
   * @param {string} group
   *   The settings group on which field updates will occur.
   */
  Drupal.tableDrag.prototype.updateField = function (changedRow, group) {
    let rowSettings = this.rowSettings(group, changedRow);
    const $changedRow = $(changedRow);
    let sourceRow;
    let $previousRow;
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
      $previousRow = $changedRow.prev('tr:first-of-type');
      previousRow = $previousRow.get(0);
      const $nextRow = $changedRow.next('tr:first-of-type');
      const nextRow = $nextRow.get(0);
      sourceRow = changedRow;
      if (
        $previousRow.is('.draggable') &&
        $previousRow.find(`.${group}`).length
      ) {
        if (this.indentEnabled) {
          if (
            $previousRow.find('.js-indentations').length ===
            $changedRow.find('.js-indentations').length
          ) {
            sourceRow = previousRow;
          }
        } else {
          sourceRow = previousRow;
        }
      } else if (
        $nextRow.is('.draggable') &&
        $nextRow.find(`.${group}`).length
      ) {
        if (this.indentEnabled) {
          if (
            $nextRow.find('.js-indentations').length ===
            $changedRow.find('.js-indentations').length
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
      $previousRow = $changedRow.prev('tr');
      previousRow = $previousRow;
      while (
        $previousRow.length &&
        $previousRow.find('.js-indentation').length >= this.rowObject.indents
      ) {
        $previousRow = $previousRow.prev('tr');
        previousRow = $previousRow;
      }
      // If we found a row.
      if ($previousRow.length) {
        sourceRow = $previousRow.get(0);
      }
      // Otherwise we went all the way to the left of the table without finding
      // a parent, meaning this item has been placed at the root level.
      else {
        // Use the first row in the table as source, because it's guaranteed to
        // be at the root level. Find the first item, then compare this row
        // against it as a sibling.
        sourceRow = $(this.table).find('tr.draggable:first-of-type').get(0);
        if (sourceRow === this.rowObject.element) {
          sourceRow = $(this.rowObject.group[this.rowObject.group.length - 1])
            .next('tr.draggable')
            .get(0);
        }
        useSibling = true;
      }
    }

    // Because we may have moved the row from one category to another,
    // take a look at our sibling and borrow its sources and targets.
    this.copyDragClasses(sourceRow, changedRow, group);
    rowSettings = this.rowSettings(group, changedRow);

    // In the case that we're looking for a parent, but the row is at the top
    // of the tree, copy our sibling's values.
    if (useSibling) {
      rowSettings.relationship = 'sibling';
      rowSettings.source = rowSettings.target;
    }

    const targetClass = `.${rowSettings.target}`;
    const targetElement = $changedRow.find(targetClass).get(0);

    // Check if a target element exists in this row.
    if (targetElement) {
      const sourceClass = `.${rowSettings.source}`;
      const sourceElement = $(sourceClass, sourceRow).get(0);
      switch (rowSettings.action) {
        case 'depth':
          // Get the depth of the target row.
          targetElement.value = $(sourceElement)
            .closest('tr')
            .find('.js-indentation').length;
          break;

        case 'match':
          // Update the value.
          targetElement.value = sourceElement.value;
          break;

        case 'order': {
          const siblings = this.rowObject.findSiblings(rowSettings);
          if ($(targetElement).is('select')) {
            // Get a list of acceptable values.
            const values = [];
            $(targetElement)
              .find('option')
              .each(function () {
                values.push(this.value);
              });
            const maxVal = values[values.length - 1];
            // Populate the values in the siblings.
            $(siblings)
              .find(targetClass)
              .each(function () {
                // If there are more items than possible values, assign the
                // maximum value to the row.
                if (values.length > 0) {
                  this.value = values.shift();
                } else {
                  this.value = maxVal;
                }
              });
          } else {
            // Assume a numeric input field.
            let weight = 0;
            const $siblingTarget = $(siblings[0]).find(targetClass);
            if ($siblingTarget.length) {
              weight = parseInt($siblingTarget[0].value, 10) || 0;
            }
            $(siblings)
              .find(targetClass)
              .each(function () {
                this.value = weight;
                weight++;
              });
          }
          break;
        }
      }
    }
  };

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
  Drupal.tableDrag.prototype.copyDragClasses = function (
    sourceRow,
    targetRow,
    group,
  ) {
    const sourceElement = $(sourceRow).find(`.${group}`);
    const targetElement = $(targetRow).find(`.${group}`);
    if (sourceElement.length && targetElement.length) {
      targetElement[0].className = sourceElement[0].className;
    }
  };

  /**
   * Check the suggested scroll of the table.
   *
   * @param {number} cursorY
   *   The Y position of the cursor.
   *
   * @return {number}
   *   The suggested scroll.
   */
  Drupal.tableDrag.prototype.checkScroll = function (cursorY) {
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
    const trigger = this.scrollSettings.trigger;
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
  };

  /**
   * Set the scroll for the table.
   *
   * @param {number} scrollAmount
   *   The amount of scroll to apply to the window.
   */
  Drupal.tableDrag.prototype.setScroll = function (scrollAmount) {
    const self = this;

    this.scrollInterval = setInterval(() => {
      // Update the scroll values stored in the object.
      self.checkScroll(self.currentPointerCoords.y);
      const aboveTable = self.scrollY > self.table.topY;
      const belowTable = self.scrollY + self.windowHeight < self.table.bottomY;
      if (
        (scrollAmount > 0 && belowTable) ||
        (scrollAmount < 0 && aboveTable)
      ) {
        window.scrollBy(0, scrollAmount);
      }
    }, this.scrollSettings.interval);
  };

  /**
   * Command to restripe table properly.
   */
  Drupal.tableDrag.prototype.restripeTable = function () {
    // :even and :odd are reversed because jQuery counts from 0 and
    // we count from 1, so we're out of sync.
    // Match immediate children of the parent element to allow nesting.
    $(this.table)
      .find('> tbody > tr.draggable, > tr.draggable')
      .filter(':visible')
      .filter(':odd')
      .removeClass('odd')
      .addClass('even')
      .end()
      .filter(':even')
      .removeClass('even')
      .addClass('odd');
  };

  /**
   * Stub function. Allows a custom handler when a row begins dragging.
   *
   * @return {null}
   *   Returns null when the stub function is used.
   */
  Drupal.tableDrag.prototype.onDrag = function () {
    return null;
  };

  /**
   * Stub function. Allows a custom handler when a row is dropped.
   *
   * @return {null}
   *   Returns null when the stub function is used.
   */
  Drupal.tableDrag.prototype.onDrop = function () {
    return null;
  };

  /**
   * Constructor to make a new object to manipulate a table row.
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
   */
  Drupal.tableDrag.prototype.row = function (
    tableRow,
    method,
    indentEnabled,
    maxDepth,
    addClasses,
  ) {
    const $tableRow = $(tableRow);

    this.element = tableRow;
    this.method = method;
    this.group = [tableRow];
    this.groupDepth = $tableRow.find('.js-indentation').length;
    this.changed = false;
    this.table = $tableRow.closest('table')[0];
    this.indentEnabled = indentEnabled;
    this.maxDepth = maxDepth;
    // Direction the row is being moved.
    this.direction = '';
    if (this.indentEnabled) {
      this.indents = $tableRow.find('.js-indentation').length;
      this.children = this.findChildren(addClasses);
      this.group = this.group.concat(this.children);
      // Find the depth of this entire group.
      for (let n = 0; n < this.group.length; n++) {
        this.groupDepth = Math.max(
          $(this.group[n]).find('.js-indentation').length,
          this.groupDepth,
        );
      }
    }
  };

  /**
   * Find all children of rowObject by indentation.
   *
   * @param {bool} addClasses
   *   Whether we want to add classes to this row to indicate child
   *   relationships.
   *
   * @return {Array}
   *   An array of children of the row.
   */
  Drupal.tableDrag.prototype.row.prototype.findChildren = function (
    addClasses,
  ) {
    const parentIndentation = this.indents;
    let currentRow = $(this.element, this.table).next('tr.draggable');
    const rows = [];
    let child = 0;

    function rowIndentation(indentNum, el) {
      const self = $(el);
      if (child === 1 && indentNum === parentIndentation) {
        self.addClass('tree-child-first');
      }
      if (indentNum === parentIndentation) {
        self.addClass('tree-child');
      } else if (indentNum > parentIndentation) {
        self.addClass('tree-child-horizontal');
      }
    }

    while (currentRow.length) {
      // A greater indentation indicates this is a child.
      if (currentRow.find('.js-indentation').length > parentIndentation) {
        child++;
        rows.push(currentRow[0]);
        if (addClasses) {
          currentRow.find('.js-indentation').each(rowIndentation);
        }
      } else {
        break;
      }
      currentRow = currentRow.next('tr.draggable');
    }
    if (addClasses && rows.length) {
      $(rows[rows.length - 1])
        .find(`.js-indentation:nth-child(${parentIndentation + 1})`)
        .addClass('tree-child-last');
    }
    return rows;
  };

  /**
   * Ensure that two rows are allowed to be swapped.
   *
   * @param {HTMLElement} row
   *   DOM object for the row being considered for swapping.
   *
   * @return {bool}
   *   Whether the swap is a valid swap or not.
   */
  Drupal.tableDrag.prototype.row.prototype.isValidSwap = function (row) {
    const $row = $(row);
    if (this.indentEnabled) {
      let prevRow;
      let nextRow;
      if (this.direction === 'down') {
        prevRow = row;
        nextRow = $row.next('tr').get(0);
      } else {
        prevRow = $row.prev('tr').get(0);
        nextRow = row;
      }
      this.interval = this.validIndentInterval(prevRow, nextRow);

      // We have an invalid swap if the valid indentations interval is empty.
      if (this.interval.min > this.interval.max) {
        return false;
      }
    }

    // Do not let an un-draggable first row have anything put before it.
    if (this.table.tBodies[0].rows[0] === row && $row.is(':not(.draggable)')) {
      return false;
    }

    return true;
  };

  /**
   * Perform the swap between two rows.
   *
   * @param {string} position
   *   Whether the swap will occur 'before' or 'after' the given row.
   * @param {HTMLElement} row
   *   DOM element what will be swapped with the row group.
   */
  Drupal.tableDrag.prototype.row.prototype.swap = function (position, row) {
    // Makes sure only DOM object are passed to Drupal.detachBehaviors().
    this.group.forEach((row) => {
      Drupal.detachBehaviors(row, drupalSettings, 'move');
    });
    $(row)[position](this.group);
    // Makes sure only DOM object are passed to Drupal.attachBehaviors()s.
    this.group.forEach((row) => {
      Drupal.attachBehaviors(row, drupalSettings);
    });
    this.changed = true;
    this.onSwap(row);
  };

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
  Drupal.tableDrag.prototype.row.prototype.validIndentInterval = function (
    prevRow,
    nextRow,
  ) {
    const $prevRow = $(prevRow);
    let maxIndent;

    // Minimum indentation:
    // Do not orphan the next row.
    const minIndent = nextRow ? $(nextRow).find('.js-indentation').length : 0;

    // Maximum indentation:
    if (
      !prevRow ||
      $prevRow.is(':not(.draggable)') ||
      $(this.element).is('.tabledrag-root')
    ) {
      // Do not indent:
      // - the first row in the table,
      // - rows dragged below a non-draggable row,
      // - 'root' rows.
      maxIndent = 0;
    } else {
      // Do not go deeper than as a child of the previous row.
      maxIndent =
        $prevRow.find('.js-indentation').length +
        ($prevRow.is('.tabledrag-leaf') ? 0 : 1);
      // Limit by the maximum allowed depth for the table.
      if (this.maxDepth) {
        maxIndent = Math.min(
          maxIndent,
          this.maxDepth - (this.groupDepth - this.indents),
        );
      }
    }

    return { min: minIndent, max: maxIndent };
  };

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
  Drupal.tableDrag.prototype.row.prototype.indent = function (indentDiff) {
    const $group = $(this.group);
    // Determine the valid indentations interval if not available yet.
    if (!this.interval) {
      const prevRow = $(this.element).prev('tr').get(0);
      const nextRow = $group.eq(-1).next('tr').get(0);
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
        $group.find('.js-indentation:first-of-type').remove();
        this.indents--;
      } else {
        $group
          .find('td:first-of-type')
          .prepend(Drupal.theme('tableDragIndentation'));
        this.indents++;
      }
    }
    if (indentDiff) {
      // Update indentation for this row.
      this.changed = true;
      this.groupDepth += indentDiff;
      this.onIndent();
    }

    return indentDiff;
  };

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
  Drupal.tableDrag.prototype.row.prototype.findSiblings = function (
    rowSettings,
  ) {
    const siblings = [];
    const directions = ['prev', 'next'];
    const rowIndentation = this.indents;
    let checkRowIndentation;
    for (let d = 0; d < directions.length; d++) {
      let checkRow = $(this.element)[directions[d]]();
      while (checkRow.length) {
        // Check that the sibling contains a similar target field.
        if (checkRow.find(`.${rowSettings.target}`)) {
          // Either add immediately if this is a flat table, or check to ensure
          // that this row has the same level of indentation.
          if (this.indentEnabled) {
            checkRowIndentation = checkRow.find('.js-indentation').length;
          }

          if (!this.indentEnabled || checkRowIndentation === rowIndentation) {
            siblings.push(checkRow[0]);
          } else if (checkRowIndentation < rowIndentation) {
            // No need to keep looking for siblings when we get to a parent.
            break;
          }
        } else {
          break;
        }
        checkRow = checkRow[directions[d]]();
      }
      // Since siblings are added in reverse order for previous, reverse the
      // completed list of previous siblings. Add the current row and continue.
      if (directions[d] === 'prev') {
        siblings.reverse();
        siblings.push(this.element);
      }
    }
    return siblings;
  };

  /**
   * Remove indentation helper classes from the current row group.
   */
  Drupal.tableDrag.prototype.row.prototype.removeIndentClasses = function () {
    Object.keys(this.children || {}).forEach((n) => {
      $(this.children[n])
        .find('.js-indentation')
        .removeClass('tree-child')
        .removeClass('tree-child-first')
        .removeClass('tree-child-last')
        .removeClass('tree-child-horizontal');
    });
  };

  /**
   * Add an asterisk or other marker to the changed row.
   */
  Drupal.tableDrag.prototype.row.prototype.markChanged = function () {
    const marker = Drupal.theme('tableDragChangedMarker');
    const cell = $(this.element).find('td:first-of-type');
    if (cell.find('abbr.tabledrag-changed').length === 0) {
      cell.append(marker);
    }
  };

  /**
   * Stub function. Allows a custom handler when a row is indented.
   *
   * @return {null}
   *   Returns null when the stub function is used.
   */
  Drupal.tableDrag.prototype.row.prototype.onIndent = function () {
    return null;
  };

  /**
   * Stub function. Allows a custom handler when a row is swapped.
   *
   * @param {HTMLElement} swappedRow
   *   The element for the swapped row.
   *
   * @return {null}
   *   Returns null when the stub function is used.
   */
  Drupal.tableDrag.prototype.row.prototype.onSwap = function (swappedRow) {
    return null;
  };

  $.extend(
    Drupal.theme,
    /** @lends Drupal.theme */ {
      /**
       * @return {string}
       *  Markup for the marker.
       */
      tableDragChangedMarker() {
        return `<abbr class="warning tabledrag-changed" title="${Drupal.t(
          'Changed',
        )}">*</abbr>`;
      },

      /**
       * @return {string}
       *   Markup for the indentation.
       */
      tableDragIndentation() {
        return '<div class="js-indentation indentation">&nbsp;</div>';
      },

      /**
       * @return {string}
       *   Markup for the warning.
       */
      tableDragChangedWarning() {
        return `<div class="tabledrag-changed-warning messages messages--warning" role="alert">${Drupal.theme(
          'tableDragChangedMarker',
        )} ${Drupal.t('You have unsaved changes.')}</div>`;
      },

      /**
       * The button for toggling table row weight visibility.
       *
       * @return {string}
       *   HTML markup for the weight toggle button and its container.
       */
      tableDragToggle: () =>
        `<div class="tabledrag-toggle-weight-wrapper" data-drupal-selector="tabledrag-toggle-weight-wrapper">
            <button type="button" class="link tabledrag-toggle-weight" data-drupal-selector="tabledrag-toggle-weight"></button>
            </div>`,

      /**
       * The contents of the toggle weight button.
       *
       * @param {boolean} show
       *   If the table weights are currently displayed.
       *
       * @return {string}
       *  HTML markup for the weight toggle button content.s
       */
      toggleButtonContent: (show) =>
        show ? Drupal.t('Hide row weights') : Drupal.t('Show row weights'),

      /**
       * @return {string}
       *   HTML markup for a tableDrag handle.
       */
      tableDragHandle() {
        return `<a href="#" title="${Drupal.t('Drag to re-order')}"
        class="tabledrag-handle"><div class="handle"></div></a>`;
      },
    },
  );
})(jQuery, Drupal, drupalSettings);
