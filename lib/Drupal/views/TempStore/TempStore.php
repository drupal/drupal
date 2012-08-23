<?php

/**
 * @file
 * Definition of Drupal\views\TempStore\TempStore.
 */

namespace Drupal\views\TempStore;

/**
 * Handles reading and writing to a non-volatile temporary storage area.
 *
 * A TempStore is not a true cache, because it is non-volatile. While a cache
 * can be reconstructed if the data disappears (i.e, a backend goes away
 * or a cache is cleared), TempStore cannot tolerate the data disappearing.
 *
 * It is primarily used to handle in-progress edits on complicated objects
 * in order to provide state to an ordinarily stateless HTTP transaction.
 */
class TempStore {

  /**
   * The subsystem or module that owns this TempStore.
   *
   * @var string
   */
  protected $subsystem;

  /**
   * The unique identifier for the owner of the temporary data.
   *
   * In order to ensure that users do not accidentally acquire each other's
   * changes, session IDs can be used to differentiate them. However, there
   * are cases where session IDs are not ideal. In these cases, an
   * alternative ID can be set (such as a user ID or the number 0) which
   * would indicate no special session handling is required.
   *
   * @var string
   */
  protected $ownerID;

  /**
   * Constructs a temporary storage object.
   *
   * @param string $subsystem
   *   The module or subsystem. Possible values might include 'entity',
   *   'form', 'views', etc.
   * @param string $owner_id
   *   A unique identifier for the owner of the temporary storage data.
   */
  function __construct($subsystem, $owner_id) {
    $this->subsystem = $subsystem;
    $this->ownerID = $owner_id;
  }

  /**
   * Fetches the data from the store.
   *
   * @param string $key
   *   The key to the stored object. See TempStore::set() for details.
   *
   * @return object|null
   *   The stored data object, or NULL if none exist.
   */
  function get($key) {
    $data = db_query(
      'SELECT data FROM {temp_store} WHERE owner_id = :owner_id AND subsystem = :subsystem AND temp_key = :temp_key',
      array(
        ':owner_id' => $this->ownerID,
        ':subsystem' => $this->subsystem,
        ':temp_key' => $key,
      )
    )
    ->fetchObject();
    if ($data) {
      return unserialize($data->data);
    }
  }

  /**
   * Writes the data to the store.
   *
   * @param string $key
   *   The key to the object being stored. For objects that already exist in
   *   the database somewhere else, this is typically the primary key of that
   *   object. For objects that do not already exist, this is typically 'new'
   *   or some special key that indicates that the object does not yet exist.
   * @param mixed $data
   *   The data to be cached. It will be serialized.
   *
   * @todo
   *   Using 'new' as a key might result in collisions if the same user tries
   *   to create multiple new objects simultaneously. Document a workaround?
   */
  function set($key, $data) {
    // Store the new data.
    db_merge('temp_store')
      ->key(array('temp_key' => $key))
      ->fields(array(
        'owner_id' => $this->ownerID,
        'subsystem' => $this->subsystem,
        'temp_key' => $key,
        'data' => serialize($data),
        'updated' => REQUEST_TIME,
      ))
      ->execute();
  }

  /**
   * Removes one or more objects from this store for this owner.
   *
   * @param string|array $key
   *   The key to the stored object, or an array of keys. See
   *   TempStore::set() for details.
   */
  function delete($key) {
    $this->deleteRecords($key);
  }

  /**
   * Removes one or more objects from this store for all owners.
   *
   * @param string|array $key
   *   The key to the stored object, or an array of keys. See
   *   TempStore::set() for details.
   */
  function deleteAll($key) {
    $this->deleteRecords($key, TRUE);
  }

  /**
   * Deletes database records for objects.
   *
   * @param string|array $key
   *   The key to the stored object, or an array of keys. See
   *   TempStore::set() for details.
   * @param bool $all
   *   Whether to delete all records for this key (TRUE) or just the current
   *   owner's (FALSE). Defaults to FALSE.
   */
  protected function deleteRecords($key, $all = FALSE) {
    // The query builder will automatically use an IN condition when an array
    // is passed.
    $query = db_delete('temp_store')
      ->condition('temp_key', $key)
      ->condition('subsystem', $this->subsystem);

    if (!$all) {
      $query->condition('owner_id', $this->ownerID);
    }

    $query->execute();
  }

  /**
   * Determines if the object is in use by another store for locking purposes.
   *
   * @param string $key
   *   The key to the stored object. See TempStore::set() for details.
   * @param bool $exclude_owner
   *   (optional) Whether or not to disregard the current user when determining
   *   the lock owner. Defaults to FALSE.
   *
   * @return stdClass|null
   *   An object with the user ID and updated date if found, otherwise NULL.
   */
  public function getLockOwner($key) {
    return db_query(
      'SELECT owner_id AS ownerID, updated FROM {temp_store} WHERE subsystem = :subsystem AND temp_key = :temp_key ORDER BY updated ASC',
      array(
        ':subsystem' => $this->subsystem,
        ':temp_key' => $key,
      )
    )->fetchObject();
  }

  /**
   * Checks to see if another owner has locked the object.
   *
   * @param string $key
   *   The key to the stored object. See TempStore::set() for details.
   *
   * @return stdClass|null
   *   An object with the owner ID and updated date, or NULL if there is no
   *   lock on the object belonging to a different owner.
   */
  public function isLocked($key) {
    $lock_owner = $this->getLockOwner($key);
    if ((isset($lock_owner->ownerID) && $this->ownerID != $lock_owner->ownerID)) {
      return $lock_owner;
    }
  }

  /**
   * Fetches the last updated time for multiple objects in a given subsystem.
   *
   * @param string $subsystem
   *   The module or subsystem. Possible values might include 'entity',
   *   'form', 'views', etc.
   * @param array $keys
   *   An array of keys of stored objects. See TempStore::set() for details.
   *
   * @return
   *   An associative array of objects and their last updated time, keyed by
   *   object key.
   */
  public static function testStoredObjects($subsystem, $keys) {
    return db_query(
      "SELECT t.temp_key, t.updated FROM {temp_store} t WHERE t.subsystem = :subsystem AND t.temp_key IN (:keys) ORDER BY t.updated ASC",
      array(':subsystem' => $subsystem, ':temp_keys' => $keys)
    )
    ->fetchAllAssoc('temp_key');
  }

  /**
   * Truncates all objects in all stores for a given key and subsystem.
   *
   * @param string $subsystem
   *   The module or subsystem. Possible values might include 'entity',
   *   'form', 'views', etc.
   * @param array $key
   *   The key to the stored object. See TempStore::set() for details.
   */
  public static function clearAll($subsystem, $key) {
    $query = db_delete('temp_store')
      ->condition('temp_key', $key)
      ->condition('subsystem', $subsystem);

    $query->execute();
  }

  /**
   * Truncates all objects older than a certain age, for all stores.
   *
   * @param int $age
   *   The minimum age of objects to remove, in seconds. For example, 86400 is
   *   one day. Defaults to 7 days.
   */
  public static function clearOldObjects($age = NULL) {
    if (!isset($age)) {
      // 7 days.
      $age = 86400 * 7;
    }
    db_delete('temp_store')
      ->condition('updated', REQUEST_TIME - $age, '<')
      ->execute();
  }

}
