/**
 * @file
 *  Testing behavior for the add_js command.
 */

(($, Drupal) => {
  /**
   * Test Ajax execution Order.
   *
   * @param {Drupal.Ajax} [ajax]
   *   {@link Drupal.Ajax} object created by {@link Drupal.Ajax}.
   * @param {object} response
   *   The response from the Ajax request.
   * @param {string} response.selector
   *   A jQuery selector string.
   *
   * @return {Promise}
   *  The promise that will resolve once this command has finished executing.
   */
  Drupal.AjaxCommands.prototype.ajaxCommandReturnPromise = function (
    ajax,
    response,
  ) {
    return new Promise((resolve, reject) => {
      setTimeout(() => {
        this.insert(ajax, response);
        resolve();
      }, Math.random() * 500);
    });
  };
})(jQuery, Drupal);
