/**
 * @file
 * Defines JavaScript behaviors for the media type form.
 */

(function ($, Drupal) {
  /**
   * Behaviors for setting summaries on media type form.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches summary behaviors on media type edit forms.
   */
  Drupal.behaviors.mediaTypeFormSummaries = {
    attach(context) {
      const $context = $(context);
      // Provide the vertical tab summaries.
      $context.find('#edit-workflow').drupalSetSummary((context) => {
        const vals = [];
        $(context).find('input[name^="options"]:checked').parent().each(function () {
          vals.push(Drupal.checkPlain($(this).find('label').text()));
        });
        if (!$(context).find('#edit-options-status').is(':checked')) {
          vals.unshift(Drupal.t('Not published'));
        }
        return vals.join(', ');
      });
      $(context).find('#edit-language').drupalSetSummary((context) => {
        const vals = [];

        vals.push($(context).find('.js-form-item-language-configuration-langcode select option:selected').text());

        $(context).find('input:checked').next('label').each(function () {
          vals.push(Drupal.checkPlain($(this).text()));
        });

        return vals.join(', ');
      });
    },
  };
}(jQuery, Drupal));
