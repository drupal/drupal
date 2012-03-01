<?php

/**
 * @file
 * Definition of Drupal\Core\Database\DriverNotSpecifiedException
 */

namespace Drupal\Core\Database;

use RuntimeException;

/**
 * Exception thrown if no driver is specified for a database connection.
 */
class DriverNotSpecifiedException extends RuntimeException {}
