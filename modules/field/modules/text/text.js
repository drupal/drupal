// $Id: text.js,v 1.3 2010/03/08 03:59:25 webchick Exp $

(function ($) {

/**
 * Auto-hide summary textarea if empty and show hide and unhide links.
 */
Drupal.behaviors.textSummary = {
  attach: function (context, settings) {
    $('.text-summary', context).once('text-summary', function () {
      var $widget = $(this).closest('div.field-type-text-with-summary');
      var $summary = $widget.find('div.text-summary-wrapper');
      var $summaryLabel = $summary.find('label');
      var $full = $widget.find('.form-item:has(.text-full)');
      var $fullLabel = $full.find('label');

      // Setup the edit/hide summary link.
      var $link = $('<span class="field-edit-link">(<a class="link-edit-summary" href="#">' + Drupal.t('Hide summary') + '</a>)</span>').toggle(
        function () {
          $summary.hide();
          $(this).find('a').html(Drupal.t('Edit summary')).end().appendTo($fullLabel);
          return false;
        },
        function () {
          $summary.show();
          $(this).find('a').html(Drupal.t('Hide summary')).end().appendTo($summaryLabel);
          return false;
        }
      ).appendTo($summaryLabel);

      // If no summary is set, hide the summary field.
      if ($(this).val() == '') {
        $link.click();
      }
      return;
    });
  }
};

})(jQuery);
