/**
 * @file
 * Attaches behaviors for the form_test module.
 */
(function ($, Drupal) {
  /**
   * Behavior for setting dynamic summaries.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches summary behavior on path edit forms.
   */
  Drupal.behaviors.formTestVerticalTabsSummary = {
    attach(context) {
      $(context)
        .find('[data-drupal-selector="edit-tab1"]')
        .drupalSetSummary((context) => {
          return 'Summary 1';
        });
      $(context)
        .find('[data-drupal-selector="edit-tab2"]')
        .drupalSetSummary((context) => {
          return 'Summary 2';
        });
    },
  };
})(jQuery, Drupal);
