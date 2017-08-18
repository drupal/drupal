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
    'name' => 'views.view.dblog_2851293',
    'data' => serialize(Yaml::decode(file_get_contents('core/modules/dblog/tests/modules/dblog_test_views/test_views/views.view.dblog_2851293.yml'))),
  ])
  ->execute();
