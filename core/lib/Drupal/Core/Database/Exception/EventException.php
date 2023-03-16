<?php

namespace Drupal\Core\Database\Exception;

use Drupal\Core\Database\DatabaseException;

/**
 * Exception thrown by the database event API.
 */
class EventException extends \RuntimeException implements DatabaseException {
}
