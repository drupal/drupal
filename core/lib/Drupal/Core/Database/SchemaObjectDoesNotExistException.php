<?php

/**
 * @file
 * Definition of Drupal\Core\Database\SchemaObjectDoesNotExistException
 */

namespace Drupal\Core\Database;

/**
 * Exception thrown if an object being modified doesn't exist yet.
 *
 * For example, this exception should be thrown whenever there is an attempt to
 * modify a database table, field, or index that does not currently exist in
 * the database schema.
 */
class SchemaObjectDoesNotExistException extends SchemaException implements DatabaseException { }
