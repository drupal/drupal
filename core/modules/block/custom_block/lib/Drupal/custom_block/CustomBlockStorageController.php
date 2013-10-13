<?php

/**
 * @file
 * Contains \Drupal\custom_block\CustomBlockStorageController.
 */

namespace Drupal\custom_block;

use Drupal\Core\Entity\FieldableDatabaseStorageController;
use Drupal\Core\Entity\EntityStorageControllerInterface;

/**
 * Controller class for custom blocks.
 *
 * This extends the Drupal\Core\Entity\DatabaseStorageController class,
 * adding required special handling for custom block entities.
 */
class CustomBlockStorageController extends FieldableDatabaseStorageController {

  /**
   * Overrides \Drupal\Core\Entity\DatabaseStorageController::attachLoad().
   */
  protected function attachLoad(&$blocks, $load_revision = FALSE) {
    // Create an array of block types for passing as a load argument.
    // Note that blocks at this point are still \StdClass objects returned from
    // the database.
    foreach ($blocks as $id => $entity) {
      $types[$entity->type] = $entity->type;
    }

    // Besides the list of blocks, pass one additional argument to
    // hook_custom_block_load(), containing a list of block types that were
    // loaded.
    $this->hookLoadArguments = array($types);
    parent::attachLoad($blocks, $load_revision);
  }

}
