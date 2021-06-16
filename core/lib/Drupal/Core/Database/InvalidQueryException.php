<?php

namespace Drupal\Core\Database;

/**
 * Exception thrown if a query would be invalid.
 *
 * This exception is thrown e.g. when trying to have an IN condition with an
 * empty array.
 */
class InvalidQueryException extends \InvalidArgumentException implements DatabaseException {}
