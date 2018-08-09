/**
 * @file media_library.click_to_select.es6.js
 */

(($, Drupal) => {
  /**
   * Allows users to select an element which checks a hidden checkbox.
   */
  Drupal.behaviors.ClickToSelect = {
    attach(context) {
      $('.js-click-to-select-trigger', context)
        .once('media-library-click-to-select')
        .on('click', event => {
          // Links inside the trigger should not be click-able.
          event.preventDefault();
          // Click the hidden checkbox when the trigger is clicked.
          const $input = $(event.currentTarget)
            .closest('.js-click-to-select')
            .find('.js-click-to-select-checkbox input');
          $input.prop('checked', !$input.prop('checked')).trigger('change');
        });
      $('.js-click-to-select-checkbox input', context)
        .once('media-library-click-to-select')
        .on('change', ({ currentTarget }) => {
          $(currentTarget)
            .closest('.js-click-to-select')
            .toggleClass('checked', $(currentTarget).prop('checked'));
        });
    },
  };
})(jQuery, Drupal);
