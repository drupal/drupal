<?php

/**
 * @file
 * Contains \Drupal\edit\Ajax\FieldFormValidationErrorsCommand.
 */

namespace Drupal\edit\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * AJAX command to indicate a field form was attempted to be saved but failed
 * validation and pass the validation errors.
 */
class FieldFormValidationErrorsCommand extends BaseCommand {

  /**
   * Constructs a FieldFormValidationErrorsCommand object.
   *
   * @param string $data
   *   The data to pass on to the client side.
   */
  public function __construct($data) {
    parent::__construct('editFieldFormValidationErrors', $data);
  }

}
