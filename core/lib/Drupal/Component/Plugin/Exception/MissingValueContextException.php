<?php

namespace Drupal\Component\Plugin\Exception;

/**
 * An exception class thrown when contexts exist but are missing a value.
 */
class MissingValueContextException extends ContextException {

  /**
   * MissingValueContextException constructor.
   *
   * @param string[] $contexts_without_value
   *   List of contexts with missing value.
   */
  public function __construct(array $contexts_without_value = []) {
    $message = 'Required contexts without a value: ' . implode(', ', $contexts_without_value);
    parent::__construct($message);
  }

}
