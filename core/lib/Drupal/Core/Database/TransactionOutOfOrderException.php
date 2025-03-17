<?php

namespace Drupal\Core\Database;

/**
 * Exception thrown transactions are out of order.
 *
 * This is thrown when a rollBack() resulted in other active transactions being
 * rolled-back.
 */
class TransactionOutOfOrderException extends TransactionException implements DatabaseException {}
