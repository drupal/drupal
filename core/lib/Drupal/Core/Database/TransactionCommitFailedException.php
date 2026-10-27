<?php

namespace Drupal\Core\Database;

/**
 * Exception thrown when a commit or release savepoint function fails.
 */
class TransactionCommitFailedException extends TransactionException implements DatabaseException {}
