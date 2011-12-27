<?php

namespace Drupal\Database;

use Exception;

/**
 * Exception to deny attempts to explicitly manage transactions.
 *
 * This exception will be thrown when the PDO connection commit() is called.
 * Code should never call this method directly.
 */
class DatabaseTransactionExplicitCommitNotAllowedException extends Exception { }
