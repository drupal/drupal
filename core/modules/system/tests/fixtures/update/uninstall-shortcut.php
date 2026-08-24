<?php

/**
 * @file
 * Removes the shortcut module from a fixture database.
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

// Remove shortcut from core.extension config.
$extensions = $connection->select('config')
  ->fields('config', ['data'])
  ->condition('collection', '')
  ->condition('name', 'core.extension')
  ->execute()
  ->fetchField();

if ($extensions) {
  $data = unserialize($extensions);
  if (isset($data['module']['shortcut'])) {
    unset($data['module']['shortcut']);
    $connection->update('config')
      ->fields(['data' => serialize($data)])
      ->condition('collection', '')
      ->condition('name', 'core.extension')
      ->execute();
  }
}

foreach (['claro', 'olivero'] as $theme) {
  $config_name = $theme . '.settings';
  $theme_settings = $connection->select('config')
    ->fields('config', ['data'])
    ->condition('collection', '')
    ->condition('name', $config_name)
    ->execute()
    ->fetchField();

  if ($theme_settings) {
    $settings = unserialize($theme_settings);
    if (isset($settings['third_party_settings']['shortcut'])) {
      unset($settings['third_party_settings']['shortcut']);
      $connection->update('config')
        ->fields(['data' => serialize($settings)])
        ->condition('collection', '')
        ->condition('name', $config_name)
        ->execute();
    }
  }
}

// Remove shortcut schema version.
$connection->delete('config')
  ->condition('collection', '')
  ->condition('name', 'shortcut.set.default')
  ->execute();


// Remove shortcut schema version.
$connection->delete('key_value')
  ->condition('collection', 'system.schema')
  ->condition('name', 'shortcut')
  ->execute();

// Drop Shortcut tables if they exist.
foreach (['shortcut', 'shortcut_field_date', 'shortcut_set_users'] as $table) {
  if ($connection->schema()->tableExists($table)) {
    $connection->schema()->dropTable($table);
  }
}
