<?php

/**
 * @file
 * Contains \Drupal\quickedit\Ajax\FieldFormValidationErrorsCommand.
 */

namespace Drupal\quickedit\Ajax;

use Drupal\Core\Ajax\BaseCommand;

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
    parent::__construct('quickeditFieldFormValidationErrors', $data);
  }

}
