<?php

/**
 * @file
 * Include file for test module.
 */

declare(strict_types=1);

/**
 * Implements hook_views_data_alter().
 */
function entity_test_views_data_alter(&$data) {
  $data['entity_test']['name_alias'] = $data['entity_test']['name'];
  $data['entity_test']['name_alias']['field']['real field'] = 'name';
}
