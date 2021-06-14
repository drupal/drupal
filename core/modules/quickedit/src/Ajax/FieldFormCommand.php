<?php

namespace Drupal\quickedit\Ajax;

use Drupal\Core\Ajax\BaseCommand;

/**
 * AJAX command for passing a rendered field form to Quick Edit's JavaScript
 * app.
 */
class FieldFormCommand extends BaseCommand {

  /**
   * Constructs a FieldFormCommand object.
   *
   * @param string $data
   *   The data to pass on to the client side.
   */
  public function __construct($data) {
    parent::__construct('quickeditFieldForm', $data);
  }

}
