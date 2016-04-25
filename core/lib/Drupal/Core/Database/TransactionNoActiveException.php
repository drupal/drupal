<?php

namespace Drupal\Core\Database;

/**
 * Exception for when popTransaction() is called with no active transaction.
 */
class TransactionNoActiveException extends TransactionException implements DatabaseException { }
