<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\QueryException.
 */

namespace Drupal\Core\Entity\Query;

/**
 * Exception thrown by Query() on unsupported query syntax.
 *
 * Some storage modules might not support the full range of the syntax for
 * conditions, and will raise a QueryException when an unsupported
 * condition was specified.
 */
class QueryException extends \Exception { }
