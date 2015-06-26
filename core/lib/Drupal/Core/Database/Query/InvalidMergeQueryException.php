<?php

/**
 * @file
 * Contains \Drupal\Core\Database\Query\InvalidMergeQueryException.
 */

namespace Drupal\Core\Database\Query;

use Drupal\Core\Database\DatabaseException;

/**
 * Exception thrown for merge queries that do not make semantic sense.
 *
 * There are many ways that a merge query could be malformed.  They should all
 * throw this exception and set an appropriately descriptive message.
 */
class InvalidMergeQueryException extends \InvalidArgumentException implements DatabaseException {}
