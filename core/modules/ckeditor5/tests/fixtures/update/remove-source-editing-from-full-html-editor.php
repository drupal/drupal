<?php

/**
 * @file
 * Removes the source editing plugin from the full_html editor.
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

$data = $connection->select('config')
  ->condition('name', 'editor.editor.full_html')
  ->fields('config', ['data'])
  ->execute()
  ->fetchField();
$data = unserialize($data);
// Remove the source editing plugin from the full_html editor to test that the
// 'styles' property still gets added to the list plugin.
// The basic_html editor stays unchanged to test that the 'styles' property gets
// added to the list plugin if editor has the source editing plugin.
unset($data['settings']['plugins']['ckeditor5_sourceEditing']);
$connection->update('config')
  ->condition('name', 'editor.editor.full_html')
  ->fields([
    'data' => serialize($data),
  ])
  ->execute();
