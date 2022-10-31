<?php

/**
 * @file
 * Text fixture.
 */

use Drupal\Core\Database\Database;
use Drupal\Core\Serialization\Yaml;

$connection = Database::getConnection();

$connection->insert('config')
  ->fields([
    'collection' => '',
    'name' => 'views.view.test_user_multi_value',
    'data' => serialize(Yaml::decode(file_get_contents(__DIR__ . '/views.view.test_user_multi_value.yml'))),
  ])
  ->execute();

$connection->insert('config')
  ->fields([
    'collection' => '',
    'name' => 'views.view.test_broken_config_multi_value',
    'data' => serialize(Yaml::decode(file_get_contents(__DIR__ . '/views.view.test_broken_config_multi_value.yml'))),
  ])
  ->execute();

$connection->insert('config')
  ->fields([
    'collection' => '',
    'name' => 'views.view.test_another_broken_config_multi_value',
    'data' => serialize(Yaml::decode(file_get_contents(__DIR__ . '/views.view.test_another_broken_config_multi_value.yml'))),
  ])
  ->execute();
