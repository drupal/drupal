<?php

/**
 * @file
 * Post update functions for the Workspace Update Test module.
 */

/**
 * Checks the active workspace during database updates.
 */
function workspace_update_test_post_update_check_active_workspace() {
  \Drupal::state()->set('workspace_update_test.has_active_workspace', \Drupal::service('workspaces.manager')->hasActiveWorkspace());
}
