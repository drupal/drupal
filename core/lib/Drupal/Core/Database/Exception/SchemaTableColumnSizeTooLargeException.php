<?php

namespace Drupal\Core\Database\Exception;

use Drupal\Core\Database\DatabaseException;
use Drupal\Core\Database\SchemaException;

/**
 * Exception thrown if a column size is too large on table creation.
 */
class SchemaTableColumnSizeTooLargeException extends SchemaException implements DatabaseException {
}
