<?php

/**
 * @file
 * Definition of Drupal\Core\Database\TransactionException
 */

namespace Drupal\Core\Database;

/**
 * Exception thrown by an error in a database transaction.
 */
class TransactionException extends \RuntimeException implements DatabaseException { }
