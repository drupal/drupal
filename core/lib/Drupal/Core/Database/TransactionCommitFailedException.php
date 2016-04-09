<?php

namespace Drupal\Core\Database;

/**
 * Exception thrown when a commit() function fails.
 */
class TransactionCommitFailedException extends TransactionException implements DatabaseException { }
