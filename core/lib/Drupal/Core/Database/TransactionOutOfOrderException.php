<?php

/**
 * @file
 * Definition of Drupal\Core\Database\TransactionOutOfOrderException
 */

namespace Drupal\Core\Database;

/**
 * Exception thrown when a rollback() resulted in other active transactions being rolled-back.
 */
class TransactionOutOfOrderException extends TransactionException implements DatabaseException { }
