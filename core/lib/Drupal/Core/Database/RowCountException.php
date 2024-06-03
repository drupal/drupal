<?php

namespace Drupal\Core\Database;

/**
 * Exception thrown if a SELECT query trying to execute rowCount() on result.
 */
class RowCountException extends \RuntimeException implements DatabaseException {

  public function __construct($message = '', $code = 0, ?\Exception $previous = NULL) {
    if (empty($message)) {
      $message = "rowCount() is supported for DELETE, INSERT, or UPDATE statements performed with structured query builders only, since they would not be portable across database engines otherwise. If the query builders are not sufficient, use a prepareStatement() with an \$allow_row_count argument set to TRUE, execute() the Statement and get the number of matched rows via rowCount().";
    }
    parent::__construct($message, $code, $previous);
  }

}
