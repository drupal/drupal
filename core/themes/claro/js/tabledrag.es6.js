/**
 * @file
 * tabledrag.js overrides and functionality extensions.
 */

(($, Drupal) => {
  /**
   * Extends core's Tabledrag functionality.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.claroTableDrag = {
    attach(context, settings) {
      /**
       * Refactors the table row markup to improve item label text wrapping.
       *
       * This addresses an issue specific to item labels that are long enough
       * to be wrapped to a new line. Without this fix, a new line may start
       * at the beginning of the table row, instead of the expected behavior of
       * starting at the x axis of the first line.
       *
       * Addressing this issue requires changing the structure of a tabledrag
       * cell's first row.
       * @example
       *   <!-- Default tabledrag structure, which has the wrapping problem. -->
       *   <tr class="draggable">
       *     <td>
       *       <!--
       *         Indentations are next to each other because they are styled as
       *         `float: left;`
       *       -->
       *       <div class="indentation"></div>
       *       <div class="indentation"></div>
       *       <a class="tabledrag-handle"></a>
       *       <!-- If the text in this link wraps enough times that the element
       *         is taller than the floated elements preceding it, some lines
       *         will wrap to the beginning of the row instead of aligning with
       *         the beginning of the link text.
       *       -->
       *       <a class="menu-item__link">A longer label that may require wrapping</a>
       *     </td>
       *     <!-- etc. -->
       *   </tr>
       * @example
       * <!-- Claro tabledrag structure, this fixes the wrapping problem. -->
       *   <tr class="draggable">
       *     <td class="tabledrag-cell">
       *       <div class="tabledrag-cell-content">
       *          <!-- Indentations are next to each other because
       *            .table-drag-cell-content is styled as `display: table-row;`
       *            and .table-drag-cell-content > * is styled as
       *            `display: table-cell;`
       *          -->
       *         <div class="indentation"></div>
       *         <div class="indentation"></div>
       *         <a class="tabledrag-handle"></a>
       *         <div class="tabledrag-cell-content__item">
       *           <!-- Placing the link inside a div styled as
       *             `display: table-cell;` allows the text to wrap within
       *             the boundaries of the "cell".
       *           -->
       *           <a class="menu-item__link">A longer label that may require wrapping</a>
       *         </div>
       *      </div>
       *    </td>
       *    <!-- additional <td> -->
       *   </tr>
       *
       * @param {number} index
       *   The index in the loop, as provided by `jQuery.each`.
       * @param {HTMLElement} row
       *   A draggable table row.
       *
       * @todo this may be removable as part of https://drupal.org/node/3083044
       */
      const createItemWrapBoundaries = (row) => {
        const $row = $(row);
        const $firstCell = $row
          .find('td:first-of-type')
          .eq(0)
          .wrapInner(Drupal.theme('tableDragCellContentWrapper'))
          .wrapInner(
            $(Drupal.theme('tableDragCellItemsWrapper')).addClass(
              'js-tabledrag-cell-content',
            ),
          );

        const $targetElem = $firstCell.find('.js-tabledrag-cell-content');

        // Move handle into the '.js-tabledrag-cell-content' target.
        $targetElem
          .eq(0)
          .find(
            '> .tabledrag-cell-content__item > .js-tabledrag-handle, > .tabledrag-cell-content__item > .js-indentation',
          )
          .prependTo($targetElem);
      };

      // Find each row in a draggable table and process it with
      // createItemWrapBoundaries().
      Object.keys(settings.tableDrag || {}).forEach((base) => {
        once(
          'claroTabledrag',
          $(context)
            .find(`#${base}`)
            .find('> tr.draggable, > tbody > tr.draggable'),
        ).forEach(createItemWrapBoundaries);
      });
    },
  };
  $.extend(Drupal.tableDrag.prototype.row.prototype, {
    /**
     * Add an asterisk or other marker to the changed row.
     *
     * @todo this may be removable as part of https://drupal.org/node/3084910
     */
    markChanged() {
      const marker = $(Drupal.theme('tableDragChangedMarker')).addClass(
        'js-tabledrag-changed-marker',
      );
      const cell = $(this.element).find('td:first-of-type');
      if (cell.find('.js-tabledrag-changed-marker').length === 0) {
        cell.find('.js-tabledrag-handle').after(marker);
      }
    },

    /**
     * Moves added indents into Claro's wrapper element.
     *
     * For indents to work properly, they must be inside the wrapper
     * created by createItemWrapBoundaries(). When an indent is added via
     * dragging, core's tabledrag functionality does not add it inside the
     * wrapper. This function fires immediately after an indent is added, which
     * moves the indent into that wrapper.
     *
     * @see Drupal.tableDrag.prototype.row.prototype.indent
     *
     * @todo this may be removable as part of https://drupal.org/node/3083044
     */
    onIndent() {
      $(this.table)
        .find('.tabledrag-cell > .js-indentation')
        .each((index, indentToMove) => {
          const $indentToMove = $(indentToMove);
          const $cellContent = $indentToMove.siblings(
            '.tabledrag-cell-content',
          );
          $indentToMove.prependTo($cellContent);
        });
    },
  });

  $.extend(
    Drupal.theme,
    /** @lends Drupal.theme */ {
      /**
       * Constructs the table drag changed marker.
       *
       * @return {string}
       *   Markup for the indentation.
       */
      tableDragIndentation() {
        return '<div class="js-indentation indentation"><svg xmlns="http://www.w3.org/2000/svg" class="tree" width="25" height="25" viewBox="0 0 25 25"><path class="tree__item tree__item-child-ltr tree__item-child-last-ltr tree__item-horizontal tree__item-horizontal-right" d="M12,12.5 H25" stroke="#888"/><path class="tree__item tree__item-child-rtl tree__item-child-last-rtl tree__item-horizontal tree__horizontal-left" d="M0,12.5 H13" stroke="#888"/><path class="tree__item tree__item-child-ltr tree__item-child-rtl tree__item-child-last-ltr tree__item-child-last-rtl tree__vertical tree__vertical-top" d="M12.5,12 v-99" stroke="#888"/><path class="tree__item tree__item-child-ltr tree__item-child-rtl tree__vertical tree__vertical-bottom" d="M12.5,12 v99" stroke="#888"/></svg></div>';
      },

      /**
       * Constructs the table drag changed warning.
       *
       * @return {string}
       *   Markup for the warning.
       */
      tableDragChangedWarning() {
        return `<div class="tabledrag-changed-warning messages messages--warning" role="alert">${Drupal.theme(
          'tableDragChangedMarker',
        )} ${Drupal.t('You have unsaved changes.')}</div>`;
      },

      /**
       * Constructs the table drag handle.
       *
       * @return {string}
       *   A string representing a DOM fragment.
       */
      tableDragHandle: () =>
        `<a href="#" title="${Drupal.t(
          'Drag to re-order',
        )}" class="tabledrag-handle js-tabledrag-handle"></a>`,

      /**
       * The button for toggling table row weight visibility.
       *
       * @return {string}
       *   HTML markup for the weight toggle button and its container.
       */
      tableDragToggle: () =>
        `<div class="tabledrag-toggle-weight-wrapper" data-drupal-selector="tabledrag-toggle-weight-wrapper">
            <button type="button" class="link action-link tabledrag-toggle-weight" data-drupal-selector="tabledrag-toggle-weight"></button>
            </div>`,

      /**
       * Constructs contents of the toggle weight button.
       *
       * @param {boolean} show
       *   If the table weights are currently displayed.
       *
       * @return {string}
       *  HTML markup for the weight toggle button content.
       */
      toggleButtonContent: (show) => {
        const classes = [
          'action-link',
          'action-link--extrasmall',
          'tabledrag-toggle-weight',
        ];
        let text = '';
        if (show) {
          classes.push('action-link--icon-hide');
          text = Drupal.t('Hide row weights');
        } else {
          classes.push('action-link--icon-show');
          text = Drupal.t('Show row weights');
        }
        return `<span class="${classes.join(' ')}">${text}</a>`;
      },

      /**
       * Constructs the wrapper for the initial content of the drag cell.
       *
       * @return {string}
       *   A string representing a DOM fragment.
       */
      tableDragCellContentWrapper() {
        return '<div class="tabledrag-cell-content__item"></div>';
      },

      /**
       * Constructs the wrapper for the whole table drag cell.
       *
       * @return {string}
       *   A string representing a DOM fragment.
       */
      tableDragCellItemsWrapper() {
        return '<div class="tabledrag-cell-content"></div>';
      },
    },
  );
})(jQuery, Drupal);
