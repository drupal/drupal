/**
 * @file
 * Splitbutton initialization.
 */

(($, Drupal) => {
  /**
   * Process elements with the [data-drupal-splitbutton-multiple] attribute.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches splitButton behaviors.
   */
  Drupal.behaviors.SplitButton = {
    attach(context) {
      const $splitbuttons = $(context)
        .find('[data-drupal-splitbutton-multiple]')
        .once('splitbutton');
      $splitbuttons.map((index, splitbutton) =>
        Drupal.splitbuttons.push(new Drupal.SplitButton(splitbutton)),
      );
    },
  };

  Drupal.splitbuttons = [];
})(jQuery, Drupal);
