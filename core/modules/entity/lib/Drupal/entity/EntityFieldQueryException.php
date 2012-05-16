<?php

/**
 * @file
 * Definition of Drupal\entity\EntityFieldQueryException.
 */

namespace Drupal\entity;

use Exception;

/**
 * Exception thrown by EntityFieldQuery() on unsupported query syntax.
 *
 * Some storage modules might not support the full range of the syntax for
 * conditions, and will raise an EntityFieldQueryException when an unsupported
 * condition was specified.
 */
class EntityFieldQueryException extends Exception { }
