<?php

namespace Drupal\Core\Database\Exception;

use Drupal\Core\Database\SchemaObjectExistsException;

/**
 * Exception thrown if a table already exists.
 */
class SchemaTableAlreadyExistsException extends SchemaObjectExistsException {
}
