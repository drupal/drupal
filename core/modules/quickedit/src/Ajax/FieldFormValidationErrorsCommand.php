<?php

namespace Drupal\quickedit\Ajax;

use Drupal\Core\Ajax\BaseCommand;

/**
 * Passes validation errors when saving a form field failed.
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
