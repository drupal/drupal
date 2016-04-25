<?php

/**
 * @file
 * Contains database additions to drupal-8.bare.standard.php.gz for testing the
 * upgrade path of editor_update_8001().
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

// Simulate an un-synchronized environment.

// Disable the 'basic_html' editor.
$data = $connection->select('config')
  ->fields('config', ['data'])
  ->condition('name', 'editor.editor.basic_html')
  ->execute()
  ->fetchField();
$data = unserialize($data);
$data['status'] = FALSE;
$connection->update('config')
  ->fields(['data' => serialize($data)])
  ->condition('name', 'editor.editor.basic_html')
  ->execute();

// Disable the 'full_html' text format.
$data = $connection->select('config')
  ->fields('config', ['data'])
  ->condition('name', 'filter.format.full_html')
  ->execute()
  ->fetchField();
$data = unserialize($data);
$data['status'] = FALSE;
$connection->update('config')
  ->fields(['data' => serialize($data)])
  ->condition('name', 'filter.format.full_html')
  ->execute();
