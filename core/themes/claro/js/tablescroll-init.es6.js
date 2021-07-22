/**
 * @file
 * Initializes scrollable tables.
 */
((Drupal, once) => {
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
      const tables = once(
        'tableScroll',
        '[data-drupal-scrollable-table]',
        context,
      );
      Drupal.TableScroll.tables = Drupal.TableScroll.tables.concat(
        tables.map((table) => new Drupal.TableScroll(table)),
      );
    },
  };
})(Drupal, once);
