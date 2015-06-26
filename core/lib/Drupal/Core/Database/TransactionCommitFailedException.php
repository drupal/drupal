<?php

/**
 * @file
 * Contains \Drupal\Core\Database\TransactionCommitFailedException.
 */

namespace Drupal\Core\Database;

/**
 * Exception thrown when a commit() function fails.
 */
class TransactionCommitFailedException extends TransactionException implements DatabaseException { }
