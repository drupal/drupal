<?php

/**
 * @file
 * Test fixture.
 */

use Drupal\Core\Database\Database;
use Drupal\Core\Serialization\Yaml;

$connection = Database::getConnection();

$config = Yaml::decode(file_get_contents('core/modules/views/tests/modules/views_test_config/test_views/views.view.test_duplicate_field_handlers.yml'));
$config['uuid'] = 'F311CC06-F69E-47DB-BBF1-BDD49CD9A669';
$connection->insert('config')
  ->fields(array(
    'collection' => '',
    'name' => 'views.view.test_duplicate_field_handlers',
    'data' => serialize($config),
  ))
  ->execute();
