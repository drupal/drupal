<?php

namespace Drupal\Database;

/**
 * Exception thrown when a savepoint or transaction name occurs twice.
 */
class TransactionNameNonUniqueException extends TransactionException { }
