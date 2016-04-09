<?php

namespace Drupal\Core\Database\Query;

use Drupal\Core\Database\DatabaseException;

/**
 * Exception thrown if an upsert query doesn't specify a unique field.
 */
class NoUniqueFieldException extends \InvalidArgumentException implements DatabaseException {}
