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
      field_attach_create_bundle('contact_message', $entity->id());
    }
    elseif ($entity->original->id() != $entity->id()) {
      field_attach_rename_bundle('contact_message', $entity->original->id(), $entity->id());
    }
  }

  /**
   * Overrides \Drupal\Core\Config\Entity\ConfigStorageController::postDelete().
   */
  protected function postDelete($entities) {
    parent::postDelete($entities);

    foreach ($entities as $entity) {
      field_attach_delete_bundle('contact_message', $entity->id());
    }
  }

}
