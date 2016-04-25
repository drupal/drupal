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
 *   \Drupal\Core\Path\PathInterface::save().
 *
 * @see \Drupal\Core\Path\PathInterface::save()
 */
function hook_path_insert($path) {
  db_insert('mytable')
    ->fields(array(
      'alias' => $path['alias'],
      'pid' => $path['pid'],
    ))
    ->execute();
}

/**
 * Respond to a path being updated.
 *
 * @param array $path
 *   The array structure is identical to that of the return value of
 *   \Drupal\Core\Path\PathInterface::save().
 *
 * @see \Drupal\Core\Path\PathInterface::save()
 */
function hook_path_update($path) {
  if ($path['alias'] != $path['original']['alias']) {
    db_update('mytable')
      ->fields(array('alias' => $path['alias']))
      ->condition('pid', $path['pid'])
      ->execute();
  }
}

/**
 * Respond to a path being deleted.
 *
 * @param array $path
 *   The array structure is identical to that of the return value of
 *   \Drupal\Core\Path\PathInterface::save().
 *
 * @see \Drupal\Core\Path\PathInterface::delete()
 */
function hook_path_delete($path) {
  db_delete('mytable')
    ->condition('pid', $path['pid'])
    ->execute();
}

/**
 * @} End of "addtogroup hooks".
 */
