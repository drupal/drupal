// $Id$

/**
 * Move a block in the blocks table from one region to another via select list.
 *
 * This behavior is dependent on the tableDrag behavior, since it uses the
 * objects initialized in that behavior to update the row.
 */
Drupal.behaviors.termDrag = function(context) {
  var table = $('#taxonomy', context);
  var tableDrag = Drupal.tableDrag.taxonomy; // Get the blocks tableDrag object.
  var rows = $('tr', table).size();

  // When a row is swapped, keep previous and next page classes set.
  tableDrag.row.prototype.onSwap = function(swappedRow) {
    $('tr.taxonomy-term-preview', table).removeClass('taxonomy-term-preview');
    $('tr.taxonomy-term-divider-top', table).removeClass('taxonomy-term-divider-top');
    $('tr.taxonomy-term-divider-bottom', table).removeClass('taxonomy-term-divider-bottom');

    if (Drupal.settings.taxonomy.backPeddle) {
      for (var n = 0; n < Drupal.settings.taxonomy.backPeddle; n++) {
        $(table[0].tBodies[0].rows[n]).addClass('taxonomy-term-preview');
      }
      $(table[0].tBodies[0].rows[Drupal.settings.taxonomy.backPeddle - 1]).addClass('taxonomy-term-divider-top');
      $(table[0].tBodies[0].rows[Drupal.settings.taxonomy.backPeddle]).addClass('taxonomy-term-divider-bottom');
    }

    if (Drupal.settings.taxonomy.forwardPeddle) {
      for (var n = rows - Drupal.settings.taxonomy.forwardPeddle - 1; n < rows - 1; n++) {
        $(table[0].tBodies[0].rows[n]).addClass('taxonomy-term-preview');
      }
      $(table[0].tBodies[0].rows[rows - Drupal.settings.taxonomy.forwardPeddle - 2]).addClass('taxonomy-term-divider-top');
      $(table[0].tBodies[0].rows[rows - Drupal.settings.taxonomy.forwardPeddle - 1]).addClass('taxonomy-term-divider-bottom');
    }
  };
};
