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
        const values = [];
        $(context)
          .find('input[name^="options"]:checked')
          .parent()
          .each(function () {
            values.push(
              Drupal.checkPlain($(this).find('label')[0].textContent),
            );
          });
        if (!$(context).find('#edit-options-status').is(':checked')) {
          values.unshift(Drupal.t('Not published'));
        }
        return values.join(', ');
      });
      $(context)
        .find('#edit-language')
        .drupalSetSummary((context) => {
          const values = [];

          values.push(
            $(context).find(
              '.js-form-item-language-configuration-langcode select option:selected',
            )[0].textContent,
          );

          $(context)
            .find('input:checked')
            .next('label')
            .each(function () {
              values.push(Drupal.checkPlain(this.textContent));
            });

          return values.join(', ');
        });
    },
  };
})(jQuery, Drupal);
