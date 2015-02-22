<?php

/**
 * @file
 * Contains Drupal\user\TempStoreException.
 */

namespace Drupal\user;

/**
 * Thrown by SharedTempStore and PrivateTempStore if they cannot acquire a lock.
 *
 * @see \Drupal\user\SharedTempStore
 * @see \Drupal\user\PrivateTempStore
 */
class TempStoreException extends \Exception {}
