<?php

/**
 * @file
 * Test fixture.
 */

use Drupal\Core\Database\Database;
use Drupal\Component\Serialization\Yaml;

$connection = Database::getConnection();

$config = Yaml::decode(file_get_contents(__DIR__ . '/views.view.test_entity_id_argument_update.yml'));

$connection->insert('config')
  ->fields([
    'collection' => '',
    'name' => 'views.view.test_entity_id_argument_update',
    'data' => serialize($config),
  ])
  ->execute();
