<?php

/**
 * @file
 * Partial database to create broken config overrides.
 *
 * @see \Drupal\system\Tests\Update\ConfigOverridesUpdateTest
 */

use Drupal\Core\Database\Database;
use Symfony\Component\Yaml\Yaml;

$connection = Database::getConnection();

// Install the incorrect override configuration.
$configs = [
  // The view has field titles translated and had an addition field added,
  // translated and then removed.
  'views.view.content',
  // The configuration has a bogus key.
  'system.cron',
];
foreach ($configs as $config_name) {
  $config = Yaml::parse(file_get_contents(__DIR__ . '/es-' . $config_name . '.yml'));
  $connection->delete('config')
    ->condition('name', $config_name)
    ->condition('collection', 'language.es')
    ->execute();
  $connection->insert('config')
    ->fields(['data', 'name', 'collection'])
    ->values([
      'name' => $config_name,
      'data' => serialize($config),
      'collection' => 'language.es',
    ])
    ->execute();
}
