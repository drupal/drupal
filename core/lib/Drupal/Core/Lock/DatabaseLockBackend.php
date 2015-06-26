<?php

/**
 * @file
 * Contains \Drupal\Core\Lock\DatabaseLockBackend.
 */

namespace Drupal\Core\Lock;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\IntegrityConstraintViolationException;

/**
 * Defines the database lock backend. This is the default backend in Drupal.
 *
 * @ingroup lock
 */
class DatabaseLockBackend extends LockBackendAbstract {

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
   * Implements Drupal\Core\Lock\LockBackedInterface::acquire().
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
        // We only retry in case the first attempt failed, but we then broke
        // an expired lock.
      } while ($retry);
    }
    return isset($this->locks[$name]);
  }

  /**
   * Implements Drupal\Core\Lock\LockBackedInterface::lockMayBeAvailable().
   */
  public function lockMayBeAvailable($name) {
    $lock = $this->database->query('SELECT expire, value FROM {semaphore} WHERE name = :name', array(':name' => $name))->fetchAssoc();
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
   * Implements Drupal\Core\Lock\LockBackedInterface::release().
   */
  public function release($name) {
    unset($this->locks[$name]);
    $this->database->delete('semaphore')
      ->condition('name', $name)
      ->condition('value', $this->getLockId())
      ->execute();
  }

  /**
   * Implements Drupal\Core\Lock\LockBackedInterface::releaseAll().
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
}
