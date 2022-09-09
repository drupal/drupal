(function ({ offCanvas }) {
  const originalResetSize = offCanvas.resetSize;

  /**
   * Wraps the Drupal.offCanvas.resetSize() method.
   *
   * @param {jQuery.Event} event
   *   The event triggered.
   * @param {object} event.data
   *   Data attached to the event.
   */
  offCanvas.resetSize = (event) => {
    originalResetSize(event);
    // Set an attribute so that tests can reliably detect when the off-canvas
    // area has been resized.
    event.data.$element.attr('data-resize-done', 'true');
  };
})(Drupal);
