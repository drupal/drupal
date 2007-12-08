// $Id: profile.js,v 1.2 2007/12/08 14:06:22 goba Exp $

/**
 * Add functionality to the profile drag and drop table.
 *
 * This behavior is dependent on the tableDrag behavior, since it uses the
 * objects initialized in that behavior to update the row. It shows and hides
 * a warning message when removing the last field from a profile category.
 */
Drupal.behaviors.profileDrag = function(context) {
  var table = $('#profile-fields');
  var tableDrag = Drupal.tableDrag['profile-fields']; // Get the profile tableDrag object.

  // Add a handler for when a row is swapped, update empty categories.
  tableDrag.row.prototype.onSwap = function(swappedRow) {
    var rowObject = this;
    $('tr.category-message', table).each(function() {
      // If the dragged row is in this category, but above the message row, swap it down one space.
      if ($(this).prev('tr').get(0) == rowObject.element) {
        // Prevent a recursion problem when using the keyboard to move rows up.
        if ((rowObject.method != 'keyboard' || rowObject.direction == 'down')) {
          rowObject.swap('after', this);
        }
      }
      // This category has become empty
      if ($(this).next('tr').is(':not(.draggable)') || $(this).next('tr').size() == 0) {
        $(this).removeClass('category-populated').addClass('category-empty');
      }
      // This category has become populated.
      else if ($(this).is('.category-empty')) {
        $(this).removeClass('category-empty').addClass('category-populated');
      }
    });
  };

  // Add a handler so when a row is dropped, update fields dropped into new categories.
  tableDrag.onDrop = function() {
    dragObject = this;
    if ($(dragObject.rowObject.element).prev('tr').is('.category-message')) {
      var categoryRow = $(dragObject.rowObject.element).prev('tr').get(0);
      var categoryNum = categoryRow.className.replace(/([^ ]+[ ]+)*category-([^ ]+)-message([ ]+[^ ]+)*/, '$2');
      var categoryField = $('select.profile-category', dragObject.rowObject.element);
      var weightField = $('select.profile-weight', dragObject.rowObject.element);
      var oldcategoryNum = weightField[0].className.replace(/([^ ]+[ ]+)*profile-weight-([^ ]+)([ ]+[^ ]+)*/, '$2');

      if (!categoryField.is('.profile-category-'+ categoryNum)) {
        categoryField.removeClass('profile-category-' + oldcategoryNum).addClass('profile-category-' + categoryNum);
        weightField.removeClass('profile-weight-' + oldcategoryNum).addClass('profile-weight-' + categoryNum);

        categoryField.val(categoryField[0].options[categoryNum].value);
      }
    }
  };
};
