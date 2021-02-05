<?php

/**
 * @file
 * Test context mapping update path by adding a layout without a context map.
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

// Add a layout plugin to an existing entity view display.
$display = $connection->select('config')
  ->fields('config', ['data'])
  ->condition('collection', '')
  ->condition('name', 'core.entity_view_display.node.article.teaser')
  ->execute()
  ->fetchField();
$display = unserialize($display);
$display['dependencies']['module'][] = 'layout_builder';
$display['dependencies']['module'][] = 'layout_discovery';
$display['third_party_settings']['layout_builder']['allow_custom'] = FALSE;
$display['third_party_settings']['layout_builder']['enabled'] = TRUE;
$display['third_party_settings']['layout_builder']['sections'][] = [
  'layout_id' => 'layout_onecol',
  'layout_settings' => ['label' => ''],
  'components' => [],
  'third_party_settings' => [],
];
$connection->update('config')
  ->fields(['data' => serialize($display)])
  ->condition('collection', '')
  ->condition('name', 'core.entity_view_display.node.article.teaser')
  ->execute();
