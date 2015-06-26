<?php

/**
 * @file
 * Contains \Drupal\Core\Database\Query\NoFieldsException.
 */

namespace Drupal\Core\Database\Query;

use Drupal\Core\Database\DatabaseException;

/**
 * Exception thrown if an insert query doesn't specify insert or default fields.
 */
class NoFieldsException extends \InvalidArgumentException implements DatabaseException {}
