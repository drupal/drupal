<?php

namespace Drupal\Database;

use Exception;

/**
 * Exception thrown when a rollback() resulted in other active transactions being rolled-back.
 */
class DatabaseTransactionOutOfOrderException extends Exception { }
