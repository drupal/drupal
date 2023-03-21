<?php

namespace Drupal\Core\Database;

/**
 * Exception thrown if server refuses connection.
 */
class DatabaseConnectionRefusedException extends \RuntimeException implements DatabaseException {}
