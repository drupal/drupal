<?php

/**
 * @file
 * Contains \Drupal\user\UserDataInterface.
 */

namespace Drupal\user;

use Drupal\Core\Database\Connection;

/**
 * Defines the user data service interface.
 */
interface UserDataInterface {

  /**
   * Returns data stored for a user account.
   *
   * @param string $module
   *   The name of the module the data is associated with.
   * @param integer $uid
   *   (optional) The user account ID the data is associated with.
   * @param string $name
   *   (optional) The name of the data key.
   *
   * @return mixed|array
   *   The requested user account data, depending on the arguments passed:
   *   - For $module, $name, and $uid, the stored value is returned, or NULL if
   *     no value was found.
   *   - For $module and $uid, an associative array is returned that contains
   *     the stored data name/value pairs.
   *   - For $module and $name, an associative array is returned whose keys are
   *     user IDs and whose values contain the stored values.
   *   - For $module only, an associative array is returned that contains all
   *     existing data for $module in all user accounts, keyed first by user ID
   *     and $name second.
   */
  public function get($module, $uid = NULL, $name = NULL);

  /**
   * Stores data for a user account.
   *
   * @param string $module
   *   The name of the module the data is associated with.
   * @param integer $uid
   *   The user account ID the data is associated with.
   * @param string $name
   *   The name of the data key.
   * @param mixed $value
   *   The value to store. Non-scalar values are serialized automatically.
   *
   * @return void
   */
  public function set($module, $uid, $name, $value);

  /**
   * Deletes data stored for a user account.
   *
   * @param string|array $module
   *   (optional) The name of the module the data is associated with. Can also
   *   be an array to delete the data of multiple modules.
   * @param integer|array $uid
   *   (optional) The user account ID the data is associated with. If omitted,
   *   all data for $module is deleted. Can also be an array of IDs to delete
   *   the data of multiple user accounts.
   * @param string $name
   *   (optional) The name of the data key. If omitted, all data associated with
   *   $module and $uid is deleted.
   *
   * @return void
   */
  public function delete($module = NULL, $uid = NULL, $name = NULL);

}
