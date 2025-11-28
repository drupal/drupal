<?php

/**
 * @file
 *
 * @deprecated in drupal:11.3.0 and is removed from drupal:12.0.0.
 *   Functions have been deprecated.
 *
 * @see https://www.drupal.org/node/3533315
 */

use Drupal\node\NodeBulkUpdate;

@trigger_error(__FILE__ . ' is deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. Functions have been deprecated. See https://www.drupal.org/project/drupal/issues/3533315', E_USER_DEPRECATED);

/**
 * Updates all nodes in the passed-in array with the passed-in field values.
 *
 * IMPORTANT NOTE: This function is intended to work when called from a form
 * submission handler. Calling it outside of the form submission process may not
 * work correctly.
 *
 * @param array $nodes
 *   Array of node nids or nodes to update.
 * @param array $updates
 *   Array of key/value pairs with node field names and the value to update that
 *   field to.
 * @param string $langcode
 *   (optional) The language updates should be applied to. If none is specified
 *   all available languages are processed.
 * @param bool $load
 *   (optional) TRUE if $nodes contains an array of node IDs to be loaded, FALSE
 *   if it contains fully loaded nodes. Defaults to FALSE.
 * @param bool $revisions
 *   (optional) TRUE if $nodes contains an array of revision IDs instead of
 *   node IDs. Defaults to FALSE; will be ignored if $load is FALSE.
 *
 * @deprecated in drupal:11.3.0 and is removed from drupal:13.0.0. Use \Drupal::service(\Drupal\node\NodeBulkUpdate::class)->process() instead.
 * @see https://www.drupal.org/node/3533315
 */
function node_mass_update(array $nodes, array $updates, $langcode = NULL, $load = FALSE, $revisions = FALSE): void {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.3.0 and is removed from drupal:13.0.0. Use \Drupal::service(\Drupal\node\NodeBulkUpdate::class)->process() instead. See https://www.drupal.org/node/3533315', E_USER_DEPRECATED);
  \Drupal::service(NodeBulkUpdate::class)->process($nodes, $updates, $langcode, $load, $revisions);
}
