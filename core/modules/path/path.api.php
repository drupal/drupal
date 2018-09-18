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
 * @see \Drupal\Core\Path\AliasStorageInterface::save()
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
 * @see \Drupal\Core\Path\AliasStorageInterface::save()
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
 * @see \Drupal\Core\Path\AliasStorageInterface::delete()
 */
function hook_path_delete($path) {
  \Drupal::database()->delete('mytable')
    ->condition('pid', $path['pid'])
    ->execute();
}

/**
 * @} End of "addtogroup hooks".
 */
