/**
 * @file
 * Initializes scrollable tables.
 */
((Drupal, $) => {
  /**
   * Attach the tableScroll function to {@link Drupal.behaviors}.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches tableScroll functionality.
   */
  Drupal.behaviors.tableScroll = {
    attach(context) {
      const $tables = $(context)
        .find('[data-drupal-scrollable-table]')
        .once('tableScroll');
      $tables.map((index, table) =>
        Drupal.TableScroll.tables.push(new Drupal.TableScroll(table)),
      );
    },
  };
})(Drupal, jQuery);
