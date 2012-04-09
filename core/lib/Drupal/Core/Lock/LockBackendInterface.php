<?php

/**
 * @file
 * Definition of Drupal\Core\Lock\LockBackendInterface.
 */

namespace Drupal\Core\Lock;

/**
 * Lock backend interface.
 */
interface LockBackendInterface {

  /**
   * Acquires a lock.
   *
   * @param string $name
   *   Lock name.
   * @param float $timeout = 30.0
   *   (optional) Lock lifetime in seconds.
   *
   * @return bool
   */
  public function acquire($name, $timeout = 30.0);

  /**
   * Checks if a lock is available for acquiring.
   *
   * @param string $name
   *   Lock to acquire.
   *
   * @return bool
   */
  public function lockMayBeAvailable($name);

  /**
   * Waits a short amount of time before a second lock acquire attempt.
   *
   * While this method is subject to have a generic implementation in abstract
   * backend implementation, some backends may provide non blocking or less I/O
   * intensive wait mecanism: this is why this method remains on the backend
   * interface.
   *
   * @param string $name
   *   Lock name currently being locked.
   * @param int $delay = 30
   *   Miliseconds to wait for.
   *
   * @return bool
   *   TRUE if the wait operation was successful and lock may be available. You
   *   still need to acquire the lock manually and it may fail again.
   */
  public function wait($name, $delay = 30);

  /**
   * Releases the given lock.
   *
   * @param string $name
   */
  public function release($name);

  /**
   * Releases all locks for the given lock token identifier.
   *
   * @param string $lockId
   *   (optional) If none given, remove all locks from the current page.
   *   Defaults to NULL.
   */
  public function releaseAll($lockId = NULL);

  /**
   * Gets the unique page token for locks. Locks will be wipeout at each end of
   * page request on a token basis.
   *
   * @return string
   */
  public function getLockId();
}
