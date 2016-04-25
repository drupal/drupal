<?php

/**
 * @file
 * Test fixture.
 */

$connection = Drupal\Core\Database\Database::getConnection();

$connection->insert('config')
  ->fields(array(
    'collection' => '',
    'name' => 'views.view.test_duplicate_field_handlers',
    'data' => serialize(\Drupal\Component\Serialization\Yaml::decode(file_get_contents('core/modules/views/tests/modules/views_test_config/test_views/views.view.test_duplicate_field_handlers.yml'))),
  ))
  ->execute();
