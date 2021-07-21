/**
 * @file media_library.main.js
 */
(($, Drupal) => {
  /**
   * Wrapper object for the current state of the media library.
   */
  Drupal.MediaLibrary = {
    /**
     * When a user interacts with the media library we want the selection to
     * persist as long as the media library modal is opened. We temporarily
     * store the selected items while the user filters the media library view or
     * navigates to different tabs.
     */
    currentSelection: [],

    /**
     * Media item click event handler that is used in multiple files.
     *
     * @param {object} event
     *   The click event.
     */
    onSelectMediaItem: (event) => {
      // Links inside the trigger should not be click-able.
      event.preventDefault();
      // Click the hidden checkbox when the trigger is clicked.
      const $input = $(event.currentTarget)
        .closest('.js-click-to-select')
        .find('.js-click-to-select-checkbox input');
      $input.prop('checked', !$input.prop('checked')).trigger('change');
    },
  };
})(jQuery, Drupal);
