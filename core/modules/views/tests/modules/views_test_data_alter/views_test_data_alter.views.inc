<?php

/**
 * @file
 * Provides views data alter isolated hook.
 */

declare(strict_types=1);

/**
 * Implements hook_views_data_alter().
 */
function views_test_data_alter_views_data_alter(array &$data): void {
  // Modify a filter to use a different filter handler plugin
  // by default so that we can test that the handler used
  // respects the handler plugin ID specified in the
  // configuration.
  $data['node_field_data']['status']['filter']['id'] = 'numeric';
}
