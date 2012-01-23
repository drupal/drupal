<?php

namespace Drupal\Core\Database;

use RuntimeException;

/**
 * Exception thrown if an undefined database connection is requested.
 */
class ConnectionNotDefinedException extends RuntimeException {}
