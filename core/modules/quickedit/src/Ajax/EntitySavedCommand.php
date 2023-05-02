<?php

namespace Drupal\quickedit\Ajax;

use Drupal\Core\Ajax\BaseCommand;

/**
 * Indicates the entity was loaded from PrivateTempStore and saved.
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
