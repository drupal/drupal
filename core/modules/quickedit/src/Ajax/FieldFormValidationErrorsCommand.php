<?php

namespace Drupal\quickedit\Ajax;

use Drupal\Core\Ajax\BaseCommand;

/**
 * Command to indicate a field failed validation.
 *
 * The saving of the field form was attempted but failed validation and passes
 * the validation errors.
 */
class FieldFormValidationErrorsCommand extends BaseCommand {

  /**
   * Constructs a FieldFormValidationErrorsCommand object.
   *
   * @param string $data
   *   The data to pass on to the client side.
   */
  public function __construct($data) {
    parent::__construct('quickeditFieldFormValidationErrors', $data);
  }

}
