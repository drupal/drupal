<?php

/**
 * @file
 * Definition of Drupal\Core\Database\TransactionExplicitCommitNotAllowedException
 */

namespace Drupal\Core\Database;

/**
 * Exception to deny attempts to explicitly manage transactions.
 *
 * This exception will be thrown when the PDO connection commit() is called.
 * Code should never call this method directly.
 */
class TransactionExplicitCommitNotAllowedException extends TransactionException implements DatabaseException { }
