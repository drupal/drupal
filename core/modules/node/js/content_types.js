/**
 * @file
 * JavaScript for the node content editing form.
 */

(function ($, Drupal) {
  /**
   * Behaviors for setting summaries on content type form.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches summary behaviors on content type edit forms.
   */
  Drupal.behaviors.contentTypes = {
    attach(context) {
      const $context = $(context);
      // Provide the vertical tab summaries.
      $context.find('#edit-submission').drupalSetSummary((context) => {
        const values = [];
        values.push(
          Drupal.checkPlain($(context).find('#edit-title-label')[0].value) ||
            Drupal.t('Requires a title'),
        );
        return values.join(', ');
      });
      $context.find('#edit-workflow').drupalSetSummary((context) => {
        const values = [];
        $(context)
          .find('input[name^="options"]:checked')
          .next('label')
          .each(function () {
            values.push(Drupal.checkPlain(this.textContent));
          });
        if ($(context).find('#edit-options-status:checked').length === 0) {
          values.unshift(Drupal.t('Not published'));
        }
        return values.join(', ');
      });
      $('#edit-language', context).drupalSetSummary((context) => {
        const values = [];

        values.push(
          $(
            '.js-form-item-language-configuration-langcode select option:selected',
            context,
          )[0].textContent,
        );

        $('input:checked', context)
          .next('label')
          .each(function () {
            values.push(Drupal.checkPlain(this.textContent));
          });

        return values.join(', ');
      });
      $context.find('#edit-display').drupalSetSummary((context) => {
        const values = [];
        const $editContext = $(context);
        $editContext
          .find('input:checked')
          .next('label')
          .each(function () {
            values.push(Drupal.checkPlain(this.textContent));
          });
        if ($editContext.find('#edit-display-submitted:checked').length === 0) {
          values.unshift(Drupal.t("Don't display post information"));
        }
        return values.join(', ');
      });
    },
  };
})(jQuery, Drupal);
