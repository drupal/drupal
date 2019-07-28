<?php

/**
 * @file
 * Adds the 'configure any layout' permission to the authenticated user.
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

$role = $connection->select('config')
  ->fields('config', ['data'])
  ->condition('collection', '')
  ->condition('name', 'user.role.authenticated')
  ->execute()
  ->fetchField();
$role = unserialize($role);

$role['permissions'][] = 'configure any layout';

$connection->update('config')
  ->fields([
    'data' => serialize($role),
    'collection' => '',
    'name' => 'user.role.authenticated',
  ])
  ->condition('collection', '')
  ->condition('name', 'user.role.authenticated')
  ->execute();
