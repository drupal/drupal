<?php

/**
 * @file
 * Test fixture.
 */

use Drupal\Core\Database\Database;
use Drupal\Core\Serialization\Yaml;

$connection = Database::getConnection();

$connection->insert('config')
  ->fields([
    'collection' => '',
    'name' => 'views.view.test_filter_taxonomy_index_tid',
    'data' => serialize(Yaml::decode(file_get_contents('core/modules/taxonomy/tests/modules/taxonomy_test_views/test_views/views.view.test_filter_taxonomy_index_tid.yml'))),
  ])
  ->execute();
