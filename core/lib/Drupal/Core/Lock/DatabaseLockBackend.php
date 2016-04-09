<?php

namespace Drupal\Core\Lock;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\IntegrityConstraintViolationException;
use Drupal\Core\Database\SchemaObjectExistsException;

/**
 * Defines the database lock backend. This is the default backend in Drupal.
 *
 * @ingroup lock
 */
class DatabaseLockBackend extends LockBackendAbstract {

  /**
   * The database table name.
   */
  const TABLE_NAME = 'semaphore';

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a new DatabaseLockBackend.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(Connection $database) {
    // __destruct() is causing problems with garbage collections, register a
    // shutdown function instead.
    drupal_register_shutdown_function(array($this, 'releaseAll'));
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public function acquire($name, $timeout = 30.0) {
    // Insure that the timeout is at least 1 ms.
    $timeout = max($timeout, 0.001);
    $expire = microtime(TRUE) + $timeout;
    if (isset($this->locks[$name])) {
      // Try to extend the expiration of a lock we already acquired.
      $success = (bool) $this->database->update('semaphore')
        ->fields(array('expire' => $expire))
        ->condition('name', $name)
        ->condition('value', $this->getLockId())
        ->execute();
      if (!$success) {
        // The lock was broken.
        unset($this->locks[$name]);
      }
      return $success;
    }
    else {
      // Optimistically try to acquire the lock, then retry once if it fails.
      // The first time through the loop cannot be a retry.
      $retry = FALSE;
      // We always want to do this code at least once.
      do {
        try {
          $this->database->insert('semaphore')
            ->fields(array(
              'name' => $name,
              'value' => $this->getLockId(),
              'expire' => $expire,
            ))
            ->execute();
          // We track all acquired locks in the global variable.
          $this->locks[$name] = TRUE;
          // We never need to try again.
          $retry = FALSE;
        }
        catch (IntegrityConstraintViolationException $e) {
          // Suppress the error. If this is our first pass through the loop,
          // then $retry is FALSE. In this case, the insert failed because some
          // other request acquired the lock but did not release it. We decide
          // whether to retry by checking lockMayBeAvailable(). This will clear
          // the offending row from the database table in case it has expired.
          $retry = $retry ? FALSE : $this->lockMayBeAvailable($name);
        }
        catch (\Exception $e) {
          // Create the semaphore table if it does not exist and retry.
          if ($this->ensureTableExists()) {
            // Retry only once.
            $retry = !$retry;
          }
          else {
            throw $e;
          }
        }
        // We only retry in case the first attempt failed, but we then broke
        // an expired lock.
      } while ($retry);
    }
    return isset($this->locks[$name]);
  }

  /**
   * {@inheritdoc}
   */
  public function lockMayBeAvailable($name) {
    try {
      $lock = $this->database->query('SELECT expire, value FROM {semaphore} WHERE name = :name', array(':name' => $name))->fetchAssoc();
    }
    catch (\Exception $e) {
      $this->catchException($e);
      // If the table does not exist yet then the lock may be available.
      $lock = FALSE;
    }
    if (!$lock) {
      return TRUE;
    }
    $expire = (float) $lock['expire'];
    $now = microtime(TRUE);
    if ($now > $expire) {
      // We check two conditions to prevent a race condition where another
      // request acquired the lock and set a new expire time. We add a small
      // number to $expire to avoid errors with float to string conversion.
      return (bool) $this->database->delete('semaphore')
        ->condition('name', $name)
        ->condition('value', $lock['value'])
        ->condition('expire', 0.0001 + $expire, '<=')
        ->execute();
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function release($name) {
    unset($this->locks[$name]);
    try {
      $this->database->delete('semaphore')
        ->condition('name', $name)
        ->condition('value', $this->getLockId())
        ->execute();
    }
    catch (\Exception $e) {
      $this->catchException($e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function releaseAll($lock_id = NULL) {
    // Only attempt to release locks if any were acquired.
    if (!empty($this->locks)) {
      $this->locks = array();
      if (empty($lock_id)) {
        $lock_id = $this->getLockId();
      }
      $this->database->delete('semaphore')
        ->condition('value', $lock_id)
        ->execute();
    }
  }

  /**
   * Check if the semaphore table exists and create it if not.
   */
  protected function ensureTableExists() {
    try {
      $database_schema = $this->database->schema();
      if (!$database_schema->tableExists(static::TABLE_NAME)) {
        $schema_definition = $this->schemaDefinition();
        $database_schema->createTable(static::TABLE_NAME, $schema_definition);
        return TRUE;
      }
    }
    // If another process has already created the semaphore table, attempting to
    // recreate it will throw an exception. In this case just catch the
    // exception and do nothing.
    catch (SchemaObjectExistsException $e) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Act on an exception when semaphore might be stale.
   *
   * If the table does not yet exist, that's fine, but if the table exists and
   * yet the query failed, then the semaphore is stale and the exception needs
   * to propagate.
   *
   * @param $e
   *   The exception.
   *
   * @throws \Exception
   */
  protected function catchException(\Exception $e) {
    if ($this->database->schema()->tableExists(static::TABLE_NAME)) {
      throw $e;
    }
  }

  /**
   * Defines the schema for the semaphore table.
   */
  public function schemaDefinition() {
    return [
      'description' => 'Table for holding semaphores, locks, flags, etc. that cannot be stored as state since they must not be cached.',
      'fields' => [
        'name' => [
          'description' => 'Primary Key: Unique name.',
          'type' => 'varchar_ascii',
          'length' => 255,
          'not null' => TRUE,
          'default' => ''
        ],
        'value' => [
          'description' => 'A value for the semaphore.',
          'type' => 'varchar_ascii',
          'length' => 255,
          'not null' => TRUE,
          'default' => ''
        ],
        'expire' => [
          'description' => 'A Unix timestamp with microseconds indicating when the semaphore should expire.',
          'type' => 'float',
          'size' => 'big',
          'not null' => TRUE
        ],
      ],
      'indexes' => [
        'value' => ['value'],
        'expire' => ['expire'],
      ],
      'primary key' => ['name'],
    ];
  }

}
