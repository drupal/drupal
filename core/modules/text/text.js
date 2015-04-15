(function ($) {

  "use strict";

  /**
   * Auto-hide summary textarea if empty and show hide and unhide links.
   */
  Drupal.behaviors.textSummary = {
    attach: function (context, settings) {
      $(context).find('.text-summary').once('text-summary').each(function () {
        var $widget = $(this).closest('.text-format-wrapper');

        var $summary = $widget.find('.text-summary-wrapper');
        var $summaryLabel = $summary.find('label').eq(0);
        var $full = $widget.find('.text-full').closest('.form-item');
        var $fullLabel = $full.find('label').eq(0);

        // Create a placeholder label when the field cardinality is greater
        // than 1.
        if ($fullLabel.length === 0) {
          $fullLabel = $('<label></label>').prependTo($full);
        }

        // Set up the edit/hide summary link.
        var $link = $('<span class="field-edit-link"> (<button type="button" class="link link-edit-summary">' + Drupal.t('Hide summary') + '</button>)</span>');
        var $button = $link.find('button');
        var toggleClick = true;
        $link.on('click', function (e) {
          if (toggleClick) {
            $summary.hide();
            $button.html(Drupal.t('Edit summary'));
            $link.appendTo($fullLabel);
          }
          else {
            $summary.show();
            $button.html(Drupal.t('Hide summary'));
            $link.appendTo($summaryLabel);
          }
          e.preventDefault();
          toggleClick = !toggleClick;
        }).appendTo($summaryLabel);

        // If no summary is set, hide the summary field.
        if ($widget.find('.text-summary').val() === '') {
          $link.trigger('click');
        }
      });
    }
  };

})(jQuery);
