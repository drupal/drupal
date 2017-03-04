<?php

/**
 * @file
 * Contains database additions to
 * drupal-8.2.1.bare.standard_with_entity_test_enabled.php.gz for testing the
 * upgrade path of https://www.drupal.org/node/2248983.
 */

use Drupal\Core\Database\Database;
use Drupal\Core\Serialization\Yaml;

$connection = Database::getConnection();

// View for the entity type "entity_test_revlog".
$views_configs[] = Yaml::decode(file_get_contents(__DIR__ . '/views.view.entity_test_revlog_for_2248983.yml'));

// View for the entity type "entity_test_mul_revlog".
$views_configs[] = Yaml::decode(file_get_contents(__DIR__ . '/views.view.entity_test_mul_revlog_for_2248983.yml'));


foreach ($views_configs as $views_config) {
  $connection->insert('config')
    ->fields([
      'collection',
      'name',
      'data',
    ])
    ->values([
      'collection' => '',
      'name' => 'views.view.' . $views_config['id'],
      'data' => serialize($views_config),
    ])
    ->execute();
}
