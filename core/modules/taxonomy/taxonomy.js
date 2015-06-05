/**
 * @file
 * Taxonomy behaviors.
 */

(function ($) {

  "use strict";

  /**
   * Move a block in the blocks table from one region to another via select list.
   *
   * This behavior is dependent on the tableDrag behavior, since it uses the
   * objects initialized in that behavior to update the row.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.termDrag = {
    attach: function (context, settings) {
      var backStep = settings.taxonomy.backStep;
      var forwardStep = settings.taxonomy.forwardStep;
      // Get the blocks tableDrag object.
      var tableDrag = Drupal.tableDrag.taxonomy;
      var $table = $('#taxonomy');
      var rows = $table.find('tr').length;

      // When a row is swapped, keep previous and next page classes set.
      tableDrag.row.prototype.onSwap = function (swappedRow) {
        $table.find('tr.taxonomy-term-preview').removeClass('taxonomy-term-preview');
        $table.find('tr.taxonomy-term-divider-top').removeClass('taxonomy-term-divider-top');
        $table.find('tr.taxonomy-term-divider-bottom').removeClass('taxonomy-term-divider-bottom');

        var tableBody = $table[0].tBodies[0];
        if (backStep) {
          for (var n = 0; n < backStep; n++) {
            $(tableBody.rows[n]).addClass('taxonomy-term-preview');
          }
          $(tableBody.rows[backStep - 1]).addClass('taxonomy-term-divider-top');
          $(tableBody.rows[backStep]).addClass('taxonomy-term-divider-bottom');
        }

        if (forwardStep) {
          for (var k = rows - forwardStep - 1; k < rows - 1; k++) {
            $(tableBody.rows[k]).addClass('taxonomy-term-preview');
          }
          $(tableBody.rows[rows - forwardStep - 2]).addClass('taxonomy-term-divider-top');
          $(tableBody.rows[rows - forwardStep - 1]).addClass('taxonomy-term-divider-bottom');
        }
      };
    }
  };

})(jQuery);
