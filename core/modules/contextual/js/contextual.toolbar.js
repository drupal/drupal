/**
 * @file
 * Attaches behaviors for the Contextual module's edit toolbar tab.
 */

(function ($, Drupal) {
  const strings = {
    tabbingReleased: Drupal.t(
      'Tabbing is no longer constrained by the Contextual module.',
    ),
    tabbingConstrained: Drupal.t(
      'Tabbing is constrained to a set of @contextualsCount and the edit mode toggle.',
    ),
    pressEsc: Drupal.t('Press the esc key to exit.'),
  };

  /**
   * Initializes a contextual link: updates its DOM, sets up model and views.
   *
   * @param {HTMLElement} context
   *   A contextual links DOM element as rendered by the server.
   */
  function initContextualToolbar(context) {
    if (!Drupal.contextual || !Drupal.contextual.instances) {
      return;
    }

    const { contextualToolbar } = Drupal;

    const viewOptions = {
      el: $('.toolbar .toolbar-bar .contextual-toolbar-tab'),
      strings,
    };
    contextualToolbar.model = new Drupal.contextual.ContextualToolbarModelView(
      viewOptions,
    );
  }

  /**
   * Attaches contextual's edit toolbar tab behavior.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches contextual toolbar behavior on a contextualToolbar-init event.
   */
  Drupal.behaviors.contextualToolbar = {
    attach(context) {
      if (once('contextualToolbar-init', 'body').length) {
        initContextualToolbar(context);
      }
    },
  };

  /**
   * Namespace for the contextual toolbar.
   *
   * @namespace
   *
   * @private
   */
  Drupal.contextualToolbar = {
    /**
     * The {@link Drupal.contextual.ContextualToolbarModelView} instance.
     *
     * @type {?Drupal.contextual.ContextualToolbarModelView}
     */
    model: null,
  };
})(jQuery, Drupal);
