/**
 * @file
 * Text behaviors.
 */

(function ($, Drupal) {
  /**
   * Auto-hide summary textarea if empty and show hide and unhide links.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches auto-hide behavior on `text-summary` events.
   */
  Drupal.behaviors.textSummary = {
    attach(context, settings) {
      $(context)
        .find('.js-text-summary')
        .once('text-summary')
        .each(function () {
          const $widget = $(this).closest('.js-text-format-wrapper');

          const $summary = $widget.find('.js-text-summary-wrapper');
          const $summaryLabel = $summary.find('label').eq(0);
          const $full = $widget.children('.js-form-type-textarea');
          let $fullLabel = $full.find('label').eq(0);

          // Create a placeholder label when the field cardinality is greater
          // than 1.
          if ($fullLabel.length === 0) {
            $fullLabel = $('<label></label>').prependTo($full);
          }

          // Set up the edit/hide summary link.
          const $link = $(
            `<span class="field-edit-link"> (<button type="button" class="link link-edit-summary">${Drupal.t(
              'Hide summary',
            )}</button>)</span>`,
          );
          const $button = $link.find('button');
          let toggleClick = true;
          $link
            .on('click', (e) => {
              if (toggleClick) {
                $summary.hide();
                $button.html(Drupal.t('Edit summary'));
                $link.appendTo($fullLabel);
              } else {
                $summary.show();
                $button.html(Drupal.t('Hide summary'));
                $link.appendTo($summaryLabel);
              }
              e.preventDefault();
              toggleClick = !toggleClick;
            })
            .appendTo($summaryLabel);

          // If no summary is set, hide the summary field.
          if ($widget.find('.js-text-summary').val() === '') {
            $link.trigger('click');
          }
        });
    },
  };
})(jQuery, Drupal);
