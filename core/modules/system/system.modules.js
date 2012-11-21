(function ($, Drupal) {

"use strict";

$.extend(Drupal.settings, {
  hideModules: {
    method: 'toggle',
    duration: 0
  }
});

/**
 * Show/hide the requirements information on modules page.
 */
Drupal.behaviors.hideModuleInformation = {
  attach: function (context, settings) {
    var $table = $('#system-modules').once('expand-modules');
    var effect = settings.hideModules;
    if ($table.length) {
      var $tbodies = $table.find('tbody');

      // Fancy animating.
      $tbodies.on('click keydown', '.description', function (e) {
        if (e.keyCode && (e.keyCode !== 13 && e.keyCode !== 32)) {
          return;
        }
        e.preventDefault();
        var $tr = $(this).closest('tr');
        var $toggleElements = $tr.find('.requirements, .links');

        $toggleElements[effect.method](effect.duration)
          .promise().done(function() {
            $tr.toggleClass('expanded');
          });

        // Change screen reader text.
        $tr.find('.module-description-prefix').text(function () {
          if ($tr.hasClass('expanded')) {
            return Drupal.t('Hide description');
          }
          else {
            return Drupal.t('Show description');
          }
        });
      });
      // Makes the whole cell a click target.
      $tbodies.on('click', 'td.checkbox', function (e) {
        e.stopPropagation();
        var input = $(this).find('input').get(0);
        if (!input.readOnly && !input.disabled) {
          input.checked = !input.checked;
        }
      });
      // Catch the event on the checkbox to avoid triggering previous handler.
      $tbodies.on('click', 'input', function (e) {
        e.stopPropagation();
      });
      // Don't close the row when clicking a link in the description.
      $tbodies.on('click', '.description a', function (e) {
        e.stopPropagation();
      });
    }
    $table.find('.requirements, .links').hide();
  }
};

}(jQuery, Drupal));
