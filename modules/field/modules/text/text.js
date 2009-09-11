// $Id$

(function ($) {

/**
 * Auto-hide summary textarea if empty and show hide and unhide links.
 */
Drupal.behaviors.textTextareaSummary = {
  attach: function (context, settings) {
    $('textarea.text-textarea-summary:not(.text-textarea-summary-processed)', context).addClass('text-textarea-summary-processed').each(function () {
      var $fieldset = $(this).closest('#body-wrapper');
      var $summary = $fieldset.find('div.text-summary-wrapper');
      var $summaryLabel = $summary.find('div.form-type-textarea label');
      var $full = $fieldset.find('div.text-full-wrapper');
      var $fullLabel = $full.find('div.form-type-textarea label');

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
