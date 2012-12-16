(function ($) {

"use strict";

/**
 * Auto-hide summary textarea if empty and show hide and unhide links.
 */
Drupal.behaviors.textSummary = {
  attach: function (context, settings) {
    $(context).find('.text-summary').once('text-summary', function () {
      var $widget = $(this).closest('.text-format-wrapper');

      var $summary = $widget.find('.text-summary-wrapper');
      var $summaryLabel = $summary.find('label');
      var $full = $widget.find('.text-full').closest('.form-item');
      var $fullLabel = $full.find('label');

      // Create a placeholder label when the field cardinality is greater
      // than 1.
      if ($fullLabel.length === 0) {
        $fullLabel = $('<label></label>').prependTo($full);
      }

      // Set up the edit/hide summary link.
      var $link = $('<span class="field-edit-link">(<a class="link-edit-summary" href="#nogo">' + Drupal.t('Hide summary') + '</a>)</span>');
      var $a = $link.find('a');
      $link.toggle(
        function (e) {
          e.preventDefault();
          $summary.hide();
          $a.html(Drupal.t('Edit summary'));
          $link.appendTo($fullLabel);
        },
        function (e) {
          e.preventDefault();
          $summary.show();
          $a.html(Drupal.t('Hide summary'));
          $link.appendTo($summaryLabel);
        }
      ).appendTo($summaryLabel);

      // If no summary is set, hide the summary field.
      if ($widget.find('.text-summary').val() === '') {
        $link.click();
      }
    });
  }
};

})(jQuery);
