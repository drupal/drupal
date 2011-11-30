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
 * Allow modules to respond to a path being inserted.
 *
 * @param $path
 *   An associative array containing the following keys:
 *   - source: The internal system path.
 *   - alias: The URL alias.
 *   - pid: Unique path alias identifier.
 *   - language: The language of the alias.
 *
 * @see path_save()
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
 * Allow modules to respond to a path being updated.
 *
 * @param $path
 *   An associative array containing the following keys:
 *   - source: The internal system path.
 *   - alias: The URL alias.
 *   - pid: Unique path alias identifier.
 *   - language: The language of the alias.
 *
 * @see path_save()
 */
function hook_path_update($path) {
  db_update('mytable')
    ->fields(array('alias' => $path['alias']))
    ->condition('pid', $path['pid'])
    ->execute();
}

/**
 * Allow modules to respond to a path being deleted.
 *
 * @param $path
 *   An associative array containing the following keys:
 *   - source: The internal system path.
 *   - alias: The URL alias.
 *   - pid: Unique path alias identifier.
 *   - language: The language of the alias.
 *
 * @see path_delete()
 */
function hook_path_delete($path) {
  db_delete('mytable')
    ->condition('pid', $path['pid'])
    ->execute();
}

/**
 * @} End of "addtogroup hooks".
 */
