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
    'name' => 'views.view.test_fix_revision_id_update',
    'data' => serialize(Yaml::decode(file_get_contents('core/modules/views/tests/fixtures/update/views.view.test_fix_revision_id_update.yml'))),
  ])
  ->execute();
