<?php

/**
 * @file
 * Definition of Drupal\edit\Ajax\FieldFormSavedCommand.
 */

namespace Drupal\edit\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * AJAX command to indicate a field form was saved without validation errors and
 * pass the rerendered field to Edit's JavaScript app.
 */
class FieldFormSavedCommand extends BaseCommand {

  /**
   * Constructs a FieldFormSavedCommand object.
   *
   * @param string $data
   *   The data to pass on to the client side.
   */
  public function __construct($data) {
    parent::__construct('editFieldFormSaved', $data);
  }

}
