<?php

/**
 * @file
 * Provides necessary database additions for testing
 * field_post_update_remove_handler_submit_setting()
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

$config = unserialize($connection->select('config', 'c')
  ->fields('c', ['data'])
  ->condition('collection', '')
  ->condition('name', 'field.field.node.article.field_tags')
  ->execute()
  ->fetchField());

$config['settings']['handler_submit'] = 'Change handler';

$connection->update('config')
  ->fields(['data' => serialize($config)])
  ->condition('collection', '')
  ->condition('name', 'field.field.node.article.field_tags')
  ->execute();
