<?php

namespace Drupal\Core\Database;

/**
 * This wrapper class serves only to provide additional debug information.
 *
 * This class will always wrap a client connection exception, for example
 * \PDOException or \mysqli_sql_exception.
 */
class DatabaseExceptionWrapper extends \RuntimeException implements DatabaseException {

}
