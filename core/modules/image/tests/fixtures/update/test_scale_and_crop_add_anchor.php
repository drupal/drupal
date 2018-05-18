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
    'name' => 'image.style.test_scale_and_crop_add_anchor',
    'data' => serialize(Yaml::decode(file_get_contents('core/modules/image/tests/fixtures/update/image.image_style.test_scale_and_crop_add_anchor.yml'))),
  ])
  ->execute();
