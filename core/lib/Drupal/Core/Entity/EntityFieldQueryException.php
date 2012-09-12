<?php

/**
 * @file
 * Definition of Drupal\Core\Entity\EntityFieldQueryException.
 */

namespace Drupal\Core\Entity;

use Exception;

/**
 * Exception thrown by EntityFieldQuery() on unsupported query syntax.
 *
 * Some storage modules might not support the full range of the syntax for
 * conditions, and will raise an EntityFieldQueryException when an unsupported
 * condition was specified.
 */
class EntityFieldQueryException extends Exception { }
