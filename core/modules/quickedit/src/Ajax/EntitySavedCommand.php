<?php

namespace Drupal\quickedit\Ajax;

use Drupal\Core\Ajax\BaseCommand;

/**
 * Command to indicate the entity was loaded from private store and saved.
 *
 * @see \Drupal\Core\TempStore\PrivateTempStore
 */
class EntitySavedCommand extends BaseCommand {

  /**
   * Constructs an EntitySaveCommand object.
   *
   * @param string $data
   *   The data to pass on to the client side.
   */
  public function __construct($data) {
    parent::__construct('quickeditEntitySaved', $data);
  }

}
