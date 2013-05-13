<?php

/**
 * @file
 * Admin page callbacks for the Action module.
 */

/**
 * Post-deletion operations for deleting action orphans.
 *
 * @param $orphaned
 *   An array of orphaned actions.
 */
function action_admin_delete_orphans_post($orphaned) {
  foreach ($orphaned as $callback) {
    drupal_set_message(t("Deleted orphaned action (%action).", array('%action' => $callback)));
  }
}
