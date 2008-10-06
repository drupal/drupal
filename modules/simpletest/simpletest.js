// $Id$

/**
 * Add the cool table collapsing on the testing overview page.
 */
Drupal.behaviors.simpleTestMenuCollapse = function() {
  // Adds expand-collapse functionality.
  $('div.simpletest-image').each(function() {
    direction = Drupal.settings.simpleTest[$(this).attr('id')].imageDirection;
    $(this).html(Drupal.settings.simpleTest.images[direction]);
  });
  $('div.simpletest-image').click(function() {
    // Toggle all of the trs.
    if (!Drupal.settings.simpleTest[$(this).attr('id')].clickActive) {
      Drupal.settings.simpleTest[$(this).attr('id')].clickActive = true;
      var trs = $(this).parents('tbody').children().filter('.' + Drupal.settings.simpleTest[$(this).attr('id')].testClass), trs_formatted = [], direction = Drupal.settings.simpleTest[$(this).attr('id')].imageDirection, self = $(this);
      for (var i = 0; i < trs.length; i++) {
        trs_formatted.push(trs[i]);
      }
      var toggleTrs = function(trs, action, action2) {
        tr = trs[action]();
        if (tr) {
          $(tr)[action2](1, function() {
            toggleTrs(trs, action, action2);
          });
        }
        else {
          Drupal.settings.simpleTest[self.attr('id')].clickActive = false;
        }
      }
      toggleTrs(trs_formatted, (direction ? 'pop' : 'shift'), (direction ? 'fadeOut' : 'fadeIn'));
      Drupal.settings.simpleTest[$(this).attr('id')].imageDirection = !direction;
      $(this).html(Drupal.settings.simpleTest.images[(direction? 0 : 1)]);
    }
  });
}

/**
 * Select/deselect all the inner checkboxes when the outer checkboxes are
 * selected/deselected.
 */
Drupal.behaviors.simpleTestSelectAll = function() {
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
};