<?php

/**
 * @file
 * Test fixture.
 */

use Drupal\Core\Database\Database;
use Drupal\Component\Serialization\Yaml;

$connection = Database::getConnection();

foreach (['olivero_syndicate', 'stark_syndicate', 'stark_custom_syndicate'] as $id) {
  $connection->insert('config')
    ->fields([
      'collection' => '',
      'name' => "block.block.$id",
      'data' => serialize(Yaml::decode(file_get_contents("core/modules/block/tests/fixtures/update/block.block.$id.yml"))),
    ])
    ->execute();
}
