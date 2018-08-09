/**
 * @file
 * Language admin behavior.
 */

(function($, Drupal) {
  /**
   * Makes language negotiation inherit user interface negotiation.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attach behavior to language negotiation admin user interface.
   */
  Drupal.behaviors.negotiationLanguage = {
    attach() {
      const $configForm = $('#language-negotiation-configure-form');
      const inputSelector = 'input[name$="[configurable]"]';
      // Given a customization checkbox derive the language type being changed.
      function toggleTable(checkbox) {
        const $checkbox = $(checkbox);
        // Get the language detection type such as Interface text language
        // detection or Content language detection.
        $checkbox
          .closest('.table-language-group')
          .find('table, .tabledrag-toggle-weight')
          .toggle($checkbox.prop('checked'));
      }

      // Bind hide/show and rearrange customization checkboxes.
      $configForm
        .once('negotiation-language-admin-bind')
        .on('change', inputSelector, event => {
          toggleTable(event.target);
        });
      // Initially, hide language detection types that are not customized.
      $configForm
        .find(`${inputSelector}:not(:checked)`)
        .each((index, element) => {
          toggleTable(element);
        });
    },
  };
})(jQuery, Drupal);
