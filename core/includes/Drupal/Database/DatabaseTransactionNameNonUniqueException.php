<?php

namespace Drupal\Database;

use Exception;

/**
 * Exception thrown when a savepoint or transaction name occurs twice.
 */
class DatabaseTransactionNameNonUniqueException extends Exception { }
