<?php

/**
 * @file
 * Adds a menu block with a `depth` setting of 0.
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

$data = $connection->select('config')
  ->condition('name', 'block.block.olivero_account_menu')
  ->fields('config', ['data'])
  ->execute()
  ->fetchField();
$data = unserialize($data);
// Change the depth setting to 0, which the update hook should change to NULL.
// @see system_post_update_set_menu_block_depth_to_null_if_zero().
$data['settings']['depth'] = 0;
$connection->update('config')
  ->condition('name', 'block.block.olivero_account_menu')
  ->fields([
    'data' => serialize($data),
  ])
  ->execute();
