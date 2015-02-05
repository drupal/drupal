<?php

/**
 * @file
 * Provides views data and hooks for views_test_language module.
 */

/**
 * Implements hook_views_data_alter().
 */
function views_test_language_views_data_alter(array &$data) {
  // Make node titles use configurable field rendering for this test.
  // @todo No need for this once https://www.drupal.org/node/2342045 lands.
  $data['node_field_data']['title']['field']['id'] = 'field';
}
