<?php

namespace Drupal\Core\Database\Exception;

use Drupal\Core\Database\DatabaseException;
use Drupal\Core\Database\SchemaException;

/**
 * Exception thrown by the Schema Definition API.
 */
class SchemaDefinitionException extends SchemaException implements DatabaseException {
}
