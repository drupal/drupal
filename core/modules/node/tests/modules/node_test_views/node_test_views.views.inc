<?php

/**
 * @file
 * Provides views data and hooks for node_test_views module.
 */

/**
 * Implements hook_views_data_alter().
 */
function node_test_views_views_data_alter(array &$data) {
  // Make node language use the basic field handler if requested.
  if (\Drupal::state()->get('node_test_views.use_basic_handler')) {
    $data['node_field_data']['langcode']['field']['id'] = 'language';
  }
}
