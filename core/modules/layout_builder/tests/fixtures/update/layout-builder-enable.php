<?php

/**
 * @file
 * Test fixture.
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

// Add a layout plugin to an existing entity view display without explicitly
// enabling Layout Builder for this display.
$display = $connection->select('config')
  ->fields('config', ['data'])
  ->condition('collection', '')
  ->condition('name', 'core.entity_view_display.block_content.basic.default')
  ->execute()
  ->fetchField();
$display = unserialize($display);
$display['third_party_settings']['layout_builder']['sections'][] = [
  'layout_id' => 'layout_onecol',
  'layout_settings' => [],
  'components' => [
    'some-uuid' => [
      'uuid' => 'some-uuid',
      'region' => 'content',
      'configuration' => [
        'id' => 'system_powered_by_block',
      ],
      'additional' => [],
      'weight' => 0,
    ],
  ],
  'third_party_settings' => [],
];
$connection->update('config')
  ->fields([
    'data' => serialize($display),
    'collection' => '',
    'name' => 'core.entity_view_display.block_content.basic.default',
  ])
  ->condition('collection', '')
  ->condition('name', 'core.entity_view_display.block_content.basic.default')
  ->execute();
