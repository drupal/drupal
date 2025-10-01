<?php

/**
 * @file
 * Test fixture.
 */

use Drupal\Core\Database\Database;
use Drupal\Component\Serialization\Yaml;

$connection = Database::getConnection();

$config = Yaml::decode(file_get_contents(__DIR__ . '/views.view.test_display_feed.yml'));

$connection->insert('config')
  ->fields([
    'collection' => '',
    'name' => 'views.view.test_display_feed',
    'data' => serialize($config),
  ])
  ->execute();
