<?php

/**
 * @file
 * Test fixture.
 */

use Drupal\Core\Database\Database;
use Drupal\Core\Serialization\Yaml;

$connection = Database::getConnection();

// Install the view configuration.
$connection->insert('config')
  ->fields([
    'collection' => '',
    'name' => 'views.view.test_exposed_block',
    'data' => serialize(Yaml::decode(file_get_contents('core/modules/views/tests/modules/views_test_config/test_views/views.view.test_exposed_block.yml'))),
  ])
  ->execute();

// Install the block configuration.
$connection->insert('config')
  ->fields([
    'collection' => '',
    'name' => 'block.block.exposedformtest_exposed_blockpage_1',
    'data' => serialize(Yaml::decode(file_get_contents('core/modules/views/tests/fixtures/update/block.block.exposedformtest_exposed_blockpage_1.yml'))),
  ])
  ->execute();
