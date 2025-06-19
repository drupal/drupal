<?php

namespace Drupal\Core\Database\Exception;

use Drupal\Core\Database\DatabaseException;
use Drupal\Core\Database\SchemaException;

/**
 * Exception thrown if the Primary Key must be dropped before an operation.
 */
class SchemaPrimaryKeyMustBeDroppedException extends SchemaException implements DatabaseException {
}
