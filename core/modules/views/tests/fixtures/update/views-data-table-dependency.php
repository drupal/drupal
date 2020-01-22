<?php

/**
 * @file
 * Contains database additions to drupal-8.bare.standard.php.gz for testing
 * views_post_update_views_data_table_dependencies().
 */

use Drupal\Core\Database\Database;
use Drupal\Core\Serialization\Yaml;
use Drupal\views\Tests\ViewTestData;

$connection = Database::getConnection();

// Install the views_test_data module.
$extensions = $connection->select('config')
  ->fields('config', ['data'])
  ->condition('collection', '')
  ->condition('name', 'core.extension')
  ->execute()
  ->fetchField();
$extensions = unserialize($extensions);
$extensions['module']['views_test_data'] = 8000;
$connection->update('config')
  ->fields([
    'data' => serialize($extensions),
  ])
  ->condition('collection', '')
  ->condition('name', 'core.extension')
  ->execute();

$views_configs = [];
// A view that should depend on views_data_test.
$views_configs[] = Yaml::decode(file_get_contents(__DIR__ . '/views.view.test_table_dependency_update.yml'));

foreach ($views_configs as $views_config) {
  $connection->insert('config')
    ->fields([
      'collection',
      'name',
      'data',
    ])
    ->values([
      'collection' => '',
      'name' => 'views.view.' . $views_config['id'],
      'data' => serialize($views_config),
    ])
    ->execute();
}

// We need the views_test_data table to exist and state entries for
// views_test_data_schema() and views_test_data_views_data().
$schema = ViewTestData::schemaDefinition();
$connection->schema()->createTable('views_test_data', $schema['views_test_data']);
$connection->insert('key_value')
  ->fields([
    'collection',
    'name',
    'value',
  ])
  ->values([
    'collection' => 'state',
    'name' => 'views_test_data_schema',
    'value' => serialize($schema),
  ])
  ->values([
    'collection' => 'state',
    'name' => 'views_test_data_views_data',
    'value' => serialize(ViewTestData::viewsData()),
  ])
  ->execute();
