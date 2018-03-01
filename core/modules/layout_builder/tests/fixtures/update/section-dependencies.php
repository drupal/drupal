<?php

/**
 * @file
 * Test fixture.
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

// Update core.extension.
$extensions = $connection->select('config')
  ->fields('config', ['data'])
  ->condition('collection', '')
  ->condition('name', 'core.extension')
  ->execute()
  ->fetchField();
$extensions = unserialize($extensions);
$extensions['module']['layout_builder'] = 0;
$extensions['module']['layout_discovery'] = 0;
$extensions['module']['layout_test'] = 0;
$connection->update('config')
  ->fields([
    'data' => serialize($extensions),
    'collection' => '',
    'name' => 'core.extension',
  ])
  ->condition('collection', '')
  ->condition('name', 'core.extension')
  ->execute();

// Add a layout plugin with a dependency to an existing entity view display.
$display = $connection->select('config')
  ->fields('config', ['data'])
  ->condition('collection', '')
  ->condition('name', 'core.entity_view_display.node.article.teaser')
  ->execute()
  ->fetchField();
$display = unserialize($display);
$display['third_party_settings']['layout_builder']['sections'][] = [
  'layout_id' => 'layout_test_dependencies_plugin',
  'layout_settings' => [],
  'components' => [],
];
$connection->update('config')
  ->fields([
    'data' => serialize($display),
    'collection' => '',
    'name' => 'core.entity_view_display.node.article.teaser',
  ])
  ->condition('collection', '')
  ->condition('name', 'core.entity_view_display.node.article.teaser')
  ->execute();
