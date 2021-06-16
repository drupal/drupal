<?php

namespace Drupal\Core\TempStore;

/**
 * Thrown by SharedTempStore and PrivateTempStore if they cannot acquire a lock.
 *
 * @see \Drupal\Core\TempStore\SharedTempStore
 * @see \Drupal\Core\TempStore\PrivateTempStore
 */
class TempStoreException extends \Exception {}
