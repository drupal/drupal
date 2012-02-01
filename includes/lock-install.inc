<?php

/**
 * @file
 * A stub lock implementation to be used during the installation
 * process when database access is not yet available. Because Drupal's
 * install system should never be running in more than on concurrant
 * request, we can bypass any need for locking.
 */

/**
 * Initialize the locking system.
 */
function lock_init() {
}

/**
 * Acquire (or renew) a lock, but do not block if it fails.
 *
 * @return
 *   TRUE if the lock was acquired, FALSE if it failed.
 */
function lock_acquire($name, $timeout = 30.0) {
  return TRUE;
}

/**
 * Check if lock acquired by a different process may be available.
 *
 * @return
 *   TRUE if there is no lock or it was removed, FALSE otherwise.
 */
function lock_may_be_available($name) {
  return TRUE;
}

/**
 * Wait for a lock to be available.
 *
 * @return
 *   TRUE if the lock holds, FALSE if it is available.
 */
function lock_wait($name, $delay = 30) {
  return FALSE;
}

/**
 * Release a lock previously acquired by lock_acquire().
 *
 * This will release the named lock if it is still held by the current request.
 *
 * @param $name
 *   The name of the lock.
 */
function lock_release($name) {
}

/**
 * Release all previously acquired locks.
 */
function lock_release_all($lock_id = NULL) {
}
