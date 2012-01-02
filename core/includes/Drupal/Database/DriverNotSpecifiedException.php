<?php

namespace Drupal\Database;

use RuntimeException;

/**
 * Exception thrown if no driver is specified for a database connection.
 */
class DriverNotSpecifiedException extends RuntimeException {}
