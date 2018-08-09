/**
 * @file
 * Javascript for the node content editing form.
 */

(function($, Drupal) {
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
      $context.find('#edit-submission').drupalSetSummary(context => {
        const vals = [];
        vals.push(
          Drupal.checkPlain(
            $(context)
              .find('#edit-title-label')
              .val(),
          ) || Drupal.t('Requires a title'),
        );
        return vals.join(', ');
      });
      $context.find('#edit-workflow').drupalSetSummary(context => {
        const vals = [];
        $(context)
          .find('input[name^="options"]:checked')
          .next('label')
          .each(function() {
            vals.push(Drupal.checkPlain($(this).text()));
          });
        if (
          !$(context)
            .find('#edit-options-status')
            .is(':checked')
        ) {
          vals.unshift(Drupal.t('Not published'));
        }
        return vals.join(', ');
      });
      $('#edit-language', context).drupalSetSummary(context => {
        const vals = [];

        vals.push(
          $(
            '.js-form-item-language-configuration-langcode select option:selected',
            context,
          ).text(),
        );

        $('input:checked', context)
          .next('label')
          .each(function() {
            vals.push(Drupal.checkPlain($(this).text()));
          });

        return vals.join(', ');
      });
      $context.find('#edit-display').drupalSetSummary(context => {
        const vals = [];
        const $editContext = $(context);
        $editContext
          .find('input:checked')
          .next('label')
          .each(function() {
            vals.push(Drupal.checkPlain($(this).text()));
          });
        if (!$editContext.find('#edit-display-submitted').is(':checked')) {
          vals.unshift(Drupal.t("Don't display post information"));
        }
        return vals.join(', ');
      });
    },
  };
})(jQuery, Drupal);
