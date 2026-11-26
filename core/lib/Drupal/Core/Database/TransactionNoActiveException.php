<?php

namespace Drupal\Core\Database;

/**
 * Exception for transaction operations called with no active transaction.
 */
class TransactionNoActiveException extends TransactionException {}
