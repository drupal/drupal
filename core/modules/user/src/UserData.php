<?php

/**
 * @file
 * Contains \Drupal\user\UserData.
 */

namespace Drupal\user;

use Drupal\Core\Database\Connection;

/**
 * Defines the user data service.
 */
class UserData implements UserDataInterface {

  /**
   * The database connection to use.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs a new user data service.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection to use.
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public function get($module, $uid = NULL, $name = NULL) {
    $query = $this->connection->select('users_data', 'ud')
      ->fields('ud')
      ->condition('module', $module);
    if (isset($uid)) {
      $query->condition('uid', $uid);
    }
    if (isset($name)) {
      $query->condition('name', $name);
    }
    $result = $query->execute();
    // If $module, $uid, and $name was passed, return the value.
    if (isset($name) && isset($uid)) {
      $result = $result->fetchAllAssoc('uid');
      if (isset($result[$uid])) {
        return $result[$uid]->serialized ? unserialize($result[$uid]->value) : $result[$uid]->value;
      }
      return NULL;
    }
    // If $module and $uid was passed, return the name/value pairs.
    elseif (isset($uid)) {
      $return = array();
      foreach ($result as $record) {
        $return[$record->name] = ($record->serialized ? unserialize($record->value) : $record->value);
      }
      return $return;
    }
    // If $module and $name was passed, return the uid/value pairs.
    elseif (isset($name)) {
      $return = array();
      foreach ($result as $record) {
        $return[$record->uid] = ($record->serialized ? unserialize($record->value) : $record->value);
      }
      return $return;
    }
    // If only $module was passed, return data keyed by uid and name.
    else {
      $return = array();
      foreach ($result as $record) {
        $return[$record->uid][$record->name] = ($record->serialized ? unserialize($record->value) : $record->value);
      }
      return $return;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function set($module, $uid, $name, $value) {
    $serialized = 0;
    if (!is_scalar($value)) {
      $value = serialize($value);
      $serialized = 1;
    }
    $this->connection->merge('users_data')
      ->keys(array(
        'uid' => $uid,
        'module' => $module,
        'name' => $name,
      ))
      ->fields(array(
        'value' => $value,
        'serialized' => $serialized,
      ))
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function delete($module = NULL, $uid = NULL, $name = NULL) {
    $query = $this->connection->delete('users_data');
    // Cast scalars to array so we can consistently use an IN condition.
    if (isset($module)) {
      $query->condition('module', (array) $module, 'IN');
    }
    if (isset($uid)) {
      $query->condition('uid', (array) $uid, 'IN');
    }
    if (isset($name)) {
      $query->condition('name', (array) $name, 'IN');
    }
    $query->execute();
  }

}
