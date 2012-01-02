<?php

namespace Drupal\Database;

/**
 * Exception thrown when a rollback() resulted in other active transactions being rolled-back.
 */
class TransactionOutOfOrderException extends TransactionException { }
