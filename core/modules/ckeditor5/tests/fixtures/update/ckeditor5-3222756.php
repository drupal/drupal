<?php

/**
 * @file
 * Test fixture.
 */

use Drupal\Core\Database\Database;
use Drupal\Core\Serialization\Yaml;

$connection = Database::getConnection();

// Update core.extension.
$extensions = $connection->select('config')
  ->fields('config', ['data'])
  ->condition('collection', '')
  ->condition('name', 'core.extension')
  ->execute()
  ->fetchField();
$extensions = unserialize($extensions);
$extensions['module']['ckeditor5'] = 0;
$connection->update('config')
  ->fields(['data' => serialize($extensions)])
  ->condition('collection', '')
  ->condition('name', 'core.extension')
  ->execute();

// Add all ckeditor5_removed_post_updates() as existing updates.
require_once __DIR__ . '/../../../../ckeditor5/ckeditor5.post_update.php';
if (function_exists('ckeditor5_removed_post_updates')) {
  $existing_updates = $connection->select('key_value')
    ->fields('key_value', ['value'])
    ->condition('collection', 'post_update')
    ->condition('name', 'existing_updates')
    ->execute()
    ->fetchField();
  $existing_updates = unserialize($existing_updates);
  $existing_updates = array_merge(
    $existing_updates,
    array_keys(ckeditor5_removed_post_updates()),
  );
  $connection->update('key_value')
    ->fields(['value' => serialize($existing_updates)])
    ->condition('collection', 'post_update')
    ->condition('name', 'existing_updates')
    ->execute();
}

$test_format_image_format = Yaml::decode(file_get_contents(__DIR__ . '/filter.format.test_format_image.yml'));
$connection->insert('config')
  ->fields([
    'collection',
    'name',
    'data',
  ])
  ->values([
    'collection' => '',
    'name' => 'filter.format.test_format_image',
    'data' => serialize($test_format_image_format),
  ])
  ->execute();

$test_format_image_editor = Yaml::decode(file_get_contents(__DIR__ . '/editor.editor.test_format_image.yml'));
$connection->insert('config')
  ->fields([
    'collection',
    'name',
    'data',
  ])
  ->values([
    'collection' => '',
    'name' => 'editor.editor.test_format_image',
    'data' => serialize($test_format_image_editor),
  ])
  ->execute();
