<?php

namespace Drupal\Core\Database;

/**
 * This wrapper class serves only to provide additional debug information.
 *
 * This class will always wrap a PDOException.
 */
class DatabaseExceptionWrapper extends \RuntimeException implements DatabaseException {

}
