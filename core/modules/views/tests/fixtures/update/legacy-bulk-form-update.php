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
    'name' => 'views.view.legacy_bulk_form',
    'data' => serialize(Yaml::decode(file_get_contents(__DIR__ . '/views.view.legacy_bulk_form.yml'))),
  ])
  ->execute();
