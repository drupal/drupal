<?php

/**
 * @file
 * Test fixture.
 */

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Database\Database;

$connection = Database::getConnection();

$connection->insert('config')
  ->fields(array(
    'collection' => '',
    'name' => 'views.view.test_duplicate_field_handlers',
    'data' => serialize(Yaml::decode(file_get_contents('core/modules/views/tests/modules/views_test_config/test_views/views.view.test_duplicate_field_handlers.yml'))),
  ))
  ->execute();
