<?php

/**
 * @file
 * Contains \Drupal\custom_block\CustomBlockTypeStorageController.
 */

namespace Drupal\custom_block;

use Drupal\Core\Config\Entity\ConfigStorageController;
use Drupal\Core\Entity\EntityInterface;

/**
 * Controller class for custom block types.
 */
class CustomBlockTypeStorageController extends ConfigStorageController {

  /**
   * Overrides \Drupal\Core\Config\Entity\ConfigStorageController::postSave().
   */
  protected function postSave(EntityInterface $entity, $update) {
    parent::postSave($entity, $update);

    if (!$update) {
      entity_invoke_bundle_hook('create', 'custom_block', $entity->id());
      custom_block_add_body_field($entity->id());
    }
    elseif ($entity->original->id() != $entity->id()) {
      entity_invoke_bundle_hook('rename', 'custom_block', $entity->original->id(), $entity->id());
    }
  }

  /**
   * Overrides \Drupal\Core\Config\Entity\ConfigStorageController::postDelete().
   */
  protected function postDelete($entities) {
    parent::postDelete($entities);

    foreach ($entities as $entity) {
      entity_invoke_bundle_hook('delete', 'custom_block', $entity->id());
    }
  }

}
