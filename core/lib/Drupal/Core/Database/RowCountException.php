<?php

/**
 * @file
 * Contains \Drupal\Core\Database\RowCountException
 */

namespace Drupal\Core\Database;

/**
 * Exception thrown if a SELECT query trying to execute rowCount() on result.
 */
class RowCountException extends \RuntimeException implements DatabaseException { }
