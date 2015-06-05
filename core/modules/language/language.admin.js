/**
 * @file
 * Language admin behavior.
 */

(function ($, Drupal) {

  "use strict";

  /**
   * Makes language negotiation inherit user interface negotiation.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.negotiationLanguage = {
    attach: function () {
      var $configForm = $('#language-negotiation-configure-form');
      var inputSelector = 'input[name$="[configurable]"]';
      // Given a customization checkbox derive the language type being changed.
      function toggleTable(checkbox) {
        var $checkbox = $(checkbox);
        // Get the language detection type such as Interface text language
        // detection or Content language detection.
        $checkbox.closest('.table-language-group')
          .find('table, .tabledrag-toggle-weight')
          .toggle($checkbox.prop('checked'));
      }

      // Bind hide/show and rearrange customization checkboxes.
      $configForm.once('negotiation-language-admin-bind').on('change', inputSelector, function (event) {
        toggleTable(event.target);
      });
      // Initially, hide language detection types that are not customized.
      $configForm.find(inputSelector + ':not(:checked)').each(function (index, element) {
        toggleTable(element);
      });
    }
  };

})(jQuery, Drupal);
