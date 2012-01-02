<?php

namespace Drupal\Database\Query;

use RuntimeException;

/**
 * Exception thrown if an insert query specifies a field twice.
 *
 * It is not allowed to specify a field as default and insert field, this
 * exception is thrown if that is the case.
 */
class FieldsOverlapException extends RuntimeException {}
