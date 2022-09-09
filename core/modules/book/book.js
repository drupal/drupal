/**
 * @file
 * JavaScript behaviors for the Book module.
 */

(function ($, Drupal) {
  /**
   * Adds summaries to the book outline form.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches summary behavior to book outline forms.
   */
  Drupal.behaviors.bookDetailsSummaries = {
    attach(context) {
      $(context)
        .find('.book-outline-form')
        .drupalSetSummary((context) => {
          const $select = $(context).find('.book-title-select');
          const val = $select[0].value;

          if (val === '0') {
            return Drupal.t('Not in book');
          }
          if (val === 'new') {
            return Drupal.t('New book');
          }
          return Drupal.checkPlain($select.find(':selected')[0].textContent);
        });
    },
  };
})(jQuery, Drupal);
