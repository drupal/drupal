<?php

/**
 * @file
 * Hooks provided by the Path module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Respond to a path being inserted.
 *
 * @param array $path
 *   The array structure is identical to that of the return value of
 *   \Drupal\Core\Path\AliasStorageInterface::save().
 *
 * @deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use
 *   hook_path_alias_insert() instead.
 *
 * @see https://www.drupal.org/node/3013865
 */
function hook_path_insert($path) {
  \Drupal::database()->insert('mytable')
    ->fields([
      'alias' => $path['alias'],
      'pid' => $path['pid'],
    ])
    ->execute();
}

/**
 * Respond to a path being updated.
 *
 * @param array $path
 *   The array structure is identical to that of the return value of
 *   \Drupal\Core\Path\AliasStorageInterface::save().
 *
 * @deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use
 *   hook_path_alias_update() instead.
 *
 * @see https://www.drupal.org/node/3013865
 */
function hook_path_update($path) {
  if ($path['alias'] != $path['original']['alias']) {
    \Drupal::database()->update('mytable')
      ->fields(['alias' => $path['alias']])
      ->condition('pid', $path['pid'])
      ->execute();
  }
}

/**
 * Respond to a path being deleted.
 *
 * @param array $path
 *   The array structure is identical to that of the return value of
 *   \Drupal\Core\Path\AliasStorageInterface::save().
 *
 * @deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use
 *   hook_path_alias_delete() instead.
 *
 * @see https://www.drupal.org/node/3013865
 */
function hook_path_delete($path) {
  \Drupal::database()->delete('mytable')
    ->condition('pid', $path['pid'])
    ->execute();
}

/**
 * @} End of "addtogroup hooks".
 */
