<?php

/**
 * @file
 * Contains \Drupal\edit\Ajax\FieldFormCommand.
 */

namespace Drupal\edit\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * AJAX command for passing a rendered field form to Edit's JavaScript app.
 */
class FieldFormCommand extends BaseCommand {

  /**
   * Constructs a FieldFormCommand object.
   *
   * @param string $data
   *   The data to pass on to the client side.
   */
  public function __construct($data) {
    parent::__construct('editFieldForm', $data);
  }

}
