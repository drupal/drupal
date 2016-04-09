<?php

namespace Drupal\Core\Database;

/**
 * Exception thrown if an object being created already exists.
 *
 * For example, this exception should be thrown whenever there is an attempt to
 * create a new database table, field, or index that already exists in the
 * database schema.
 */
class SchemaObjectExistsException extends SchemaException implements DatabaseException { }
