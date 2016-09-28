<?php

/**
 * @file
 * Text fixture.
 */

use Drupal\Core\Database\Database;
use Drupal\Core\Serialization\Yaml;

$connection = Database::getConnection();

$config = Yaml::decode(file_get_contents('core/modules/views/tests/modules/views_test_config/test_views/views.view.test_token_view.yml'));
$config['uuid'] = '109F8D2E-3A03-4F32-9E56-8A1CBA5F15C5';
$connection->insert('config')
  ->fields(array(
    'collection' => '',
    'name' => 'views.view.test_token_view',
    'data' => serialize($config),
  ))
  ->execute();
