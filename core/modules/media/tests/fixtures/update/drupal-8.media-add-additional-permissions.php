<?php
// @codingStandardsIgnoreFile

/**
 * @file
 * Contains database additions to drupal-8.4.0.bare.standard.php.gz for testing
 * the upgrade path of https://www.drupal.org/node/2862422.
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

$role['permissions'][] = 'update media';
$role['permissions'][] = 'update any media';
$role['permissions'][] = 'delete media';
$role['permissions'][] = 'delete any media';
$role['permissions'][] = 'create media';

$connection->update('config')
  ->fields([
    'data' => serialize($role),
    'collection' => '',
    'name' => 'user.role.authenticated',
  ])
  ->condition('collection', '')
  ->condition('name', 'user.role.authenticated')
  ->execute();
