<?php

/**
 * @file
 * Contains Drupal\contact\CategoryStorageController.
 */

namespace Drupal\contact;

use Drupal\Core\Config\Entity\ConfigStorageController;
use Drupal\Core\Entity\EntityInterface;

/**
 * Controller class for contact categories.
 */
class CategoryStorageController extends ConfigStorageController {

  /**
   * Overrides \Drupal\Core\Config\Entity\ConfigStorageController::postSave().
   */
  protected function postSave(EntityInterface $entity, $update) {
    parent::postSave($entity, $update);

    if (!$update) {
      entity_invoke_bundle_hook('create', 'contact_message', $entity->id());
    }
    elseif ($entity->original->id() != $entity->id()) {
      entity_invoke_bundle_hook('rename', 'contact_message', $entity->original->id(), $entity->id());
    }
  }

  /**
   * Overrides \Drupal\Core\Config\Entity\ConfigStorageController::postDelete().
   */
  protected function postDelete($entities) {
    parent::postDelete($entities);

    foreach ($entities as $entity) {
      entity_invoke_bundle_hook('delete', 'contact_message', $entity->id());
    }
  }

}
