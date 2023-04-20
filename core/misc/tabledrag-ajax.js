/**
 * Ajax command for highlighting elements.
 *
 * @param {Drupal.Ajax} [ajax]
 *   An Ajax object.
 * @param {object} response
 *   The Ajax response.
 * @param {string} response.id
 *   The row id.
 * @param {string} response.tabledrag_instance
 *   The tabledrag instance identifier.
 * @param {number} [status]
 *   The HTTP status code.
 */
Drupal.AjaxCommands.prototype.tabledragChanged = function (
  ajax,
  response,
  status,
) {
  if (status !== 'success') {
    return;
  }

  const tableDrag = Drupal.tableDrag[response.tabledrag_instance];

  // eslint-disable-next-line new-cap
  const rowObject = new tableDrag.row(
    document.getElementById(response.id),
    '',
    tableDrag.indentEnabled,
    tableDrag.maxDepth,
    true,
  );
  rowObject.markChanged();
  rowObject.addChangedWarning();
};
