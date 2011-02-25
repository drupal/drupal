(function ($) {

/**
 * Add the cool table collapsing on the testing overview page.
 */
Drupal.behaviors.simpleTestMenuCollapse = {
  attach: function (context, settings) {
    var timeout = null;
    // Adds expand-collapse functionality.
    $('div.simpletest-image').each(function () {
      direction = settings.simpleTest[$(this).attr('id')].imageDirection;
      $(this).html(settings.simpleTest.images[direction]);
    });

    // Adds group toggling functionality to arrow images.
    $('div.simpletest-image').click(function () {
      var trs = $(this).parents('tbody').children('.' + settings.simpleTest[this.id].testClass);
      var direction = settings.simpleTest[this.id].imageDirection;
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
      $(this).html(settings.simpleTest.images[(direction ? 0 : 1)]);
      settings.simpleTest[this.id].imageDirection = !direction;

    });
  }
};

/**
 * Select/deselect all the inner checkboxes when the outer checkboxes are
 * selected/deselected.
 */
Drupal.behaviors.simpleTestSelectAll = {
  attach: function (context, settings) {
    $('td.simpletest-select-all').each(function () {
      var testCheckboxes = settings.simpleTest['simpletest-test-group-' + $(this).attr('id')].testNames;
      var groupCheckbox = $('<input type="checkbox" class="form-checkbox" id="' + $(this).attr('id') + '-select-all" />');

      // Each time a single-test checkbox is checked or unchecked, make sure
      // that the associated group checkbox gets the right state too.
      var updateGroupCheckbox = function () {
        var checkedTests = 0;
        for (var i = 0; i < testCheckboxes.length; i++) {
          $('#' + testCheckboxes[i]).each(function () {
            if (($(this).attr('checked'))) {
              checkedTests++;
            }
          });
        }
        $(groupCheckbox).attr('checked', (checkedTests == testCheckboxes.length));
      };

      // Have the single-test checkboxes follow the group checkbox.
      groupCheckbox.change(function () {
        var checked = !!($(this).attr('checked'));
        for (var i = 0; i < testCheckboxes.length; i++) {
          $('#' + testCheckboxes[i]).attr('checked', checked);
        }
      });

      // Have the group checkbox follow the single-test checkboxes.
      for (var i = 0; i < testCheckboxes.length; i++) {
        $('#' + testCheckboxes[i]).change(function () {
          updateGroupCheckbox();
        });
      }

      // Initialize status for the group checkbox correctly.
      updateGroupCheckbox();
      $(this).append(groupCheckbox);
    });
  }
};

})(jQuery);
