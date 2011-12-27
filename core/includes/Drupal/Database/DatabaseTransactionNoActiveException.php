<?php

namespace Drupal\Database;

use Exception;

/**
 * Exception for when popTransaction() is called with no active transaction.
 */
class DatabaseTransactionNoActiveException extends Exception { }
