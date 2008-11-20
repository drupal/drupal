// $Id$

/**
 * Add the cool table collapsing on the testing overview page.
 */
Drupal.behaviors.simpleTestMenuCollapse = {
  attach: function() {
    var timeout = null;
    // Adds expand-collapse functionality.
    $('div.simpletest-image').each(function() {
      direction = Drupal.settings.simpleTest[$(this).attr('id')].imageDirection;
      $(this).html(Drupal.settings.simpleTest.images[direction]);
    });

    // Adds group toggling functionality to arrow images.
    $('div.simpletest-image').click(function() {
      var trs = $(this).parents('tbody').children('.' + Drupal.settings.simpleTest[this.id].testClass);
      var direction = Drupal.settings.simpleTest[this.id].imageDirection;
      var row = direction ? trs.size() - 1 : 0;

      // If clicked in the middle of expanding a group, stop so we can switch directions.
      if (timeout) {
        clearTimeout(timeout);
      }

      // Function to toggle an individual row according to the current direction.
      // We set a timeout of 20 ms until the next row will be shown/hidden to
      // create a sliding effect.
      function rowToggle() {
        if (direction) {
          if (row >= 0) {
            $(trs[row]).hide();
            row--;
            timeout = setTimeout(rowToggle, 20);
          }
        }
        else {
          if (row < trs.size()) {
            $(trs[row]).removeClass('js-hide').show();
            row++;
            timeout = setTimeout(rowToggle, 20);
          }
        }
      }

      // Kick-off the toggling upon a new click.
      rowToggle();

      // Toggle the arrow image next to the test group title.
      $(this).html(Drupal.settings.simpleTest.images[(direction ? 0 : 1)]);
      Drupal.settings.simpleTest[this.id].imageDirection = !direction;

    });
  }
};

/**
 * Select/deselect all the inner checkboxes when the outer checkboxes are
 * selected/deselected.
 */
Drupal.behaviors.simpleTestSelectAll = {
  attach: function() {
    $('td.simpletest-select-all').each(function() {
      var checkboxes = Drupal.settings.simpleTest['simpletest-test-group-'+ $(this).attr('id')].testNames, totalCheckboxes = 0,
        checkbox = $('<input type="checkbox" class="form-checkbox" id="'+ $(this).attr('id') +'-select-all" />').change(function() {
        var checked = !!($(this).attr('checked'));
        for (var i = 0; i < checkboxes.length; i++) {
          $('#'+ checkboxes[i]).attr('checked', checked);
        }
        self.data('simpletest-checked-tests', (checked ? checkboxes.length : 0));
      }).data('simpletest-checked-tests', 0);
      var self = $(this);
      for (var i = 0; i < checkboxes.length; i++) {
        if ($('#' + checkboxes[i]).change(function() {
          if (checkbox.attr('checked') == 'checked') {
            checkbox.attr('checked', '');
          }
          var data = (!self.data('simpletest-checked-tests') ? 0 : self.data('simpletest-checked-tests')) + (!!($(this).attr('checked')) ? 1 : -1);
          self.data('simpletest-checked-tests', data);
          if (data == checkboxes.length) {
            checkbox.attr('checked', 'checked');
          }
          else {
            checkbox.removeAttr('checked');
          }
        }).attr('checked') == 'checked') {
          totalCheckboxes++;
        }
      }
      if (totalCheckboxes == checkboxes.length) {
        $(checkbox).attr('checked', 'checked');
      }
      $(this).append(checkbox);
    });
  }
};
