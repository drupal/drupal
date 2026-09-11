<?php

/**
 * @file
 * Test fixture.
 */

use Drupal\Core\Database\Database;
use Drupal\Component\Serialization\Yaml;

$connection = Database::getConnection();

$connection->insert('config')
  ->fields([
    'collection' => '',
    'name' => 'views.view.test_filter_min_max_labels',
    'data' => serialize(Yaml::decode(file_get_contents('core/modules/views/tests/fixtures/update/views.view.test_filter_min_max_labels.yml'))),
  ])
  ->execute();
