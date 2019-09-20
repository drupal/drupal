<?php

/**
 * @file
 * Post update functions for the Workspaces module.
 */

/**
 * Clear caches due to access changes.
 */
function workspaces_post_update_access_clear_caches() {
}

/**
 * Remove the default workspace.
 */
function workspaces_post_update_remove_default_workspace() {
  if ($workspace = \Drupal::entityTypeManager()->getStorage('workspace')->load('live')) {
    $workspace->delete();
  }
}
