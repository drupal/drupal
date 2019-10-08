<?php

namespace Drupal\Core\Config;

/**
 * Thrown by config storage transformers if they cannot acquire a lock.
 *
 * @see \Drupal\Core\Config\ImportStorageTransformer
 * @see \Drupal\Core\Config\ExportStorageManager
 */
class StorageTransformerException extends \Exception {}
