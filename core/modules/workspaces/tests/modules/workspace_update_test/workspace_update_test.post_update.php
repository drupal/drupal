<?php

/**
 * @file
 * Post update functions for the Workspace Update Test module.
 */

declare(strict_types=1);

/**
 * Checks the active workspace during database updates.
 */
function workspace_update_test_post_update_check_active_workspace(): void {
  \Drupal::state()->set('workspace_update_test.has_active_workspace', \Drupal::service('workspaces.manager')->hasActiveWorkspace());
}
