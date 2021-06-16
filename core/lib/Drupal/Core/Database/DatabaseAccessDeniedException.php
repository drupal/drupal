<?php

namespace Drupal\Core\Database;

/**
 * Exception thrown if access credentials fail.
 */
class DatabaseAccessDeniedException extends \RuntimeException implements DatabaseException {}
