/**
 * @file
 * Attaches comment behaviors to the entity form.
 */

(function ($, Drupal) {
  /**
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.commentFieldsetSummaries = {
    attach(context) {
      const $context = $(context);
      $context
        .find('fieldset.comment-entity-settings-form')
        .drupalSetSummary((context) =>
          Drupal.checkPlain(
            $(context)
              .find('.js-form-item-comment input:checked')
              .next('label')
              .text(),
          ),
        );
    },
  };
})(jQuery, Drupal);
