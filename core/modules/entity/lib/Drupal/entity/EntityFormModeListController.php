<?php

/**
 * @file
 * Contains \Drupal\entity\EntityFormModeListController.
 */

namespace Drupal\entity;

/**
 * Provides the listing for entity display modes.
 */
class EntityFormModeListController extends EntityDisplayModeListController {

  /**
   * Filters entities based on their controllers.
   *
   * @param $entity_type
   *   The entity type of the entity that needs to be validated.
   *
   * @return bool
   *   TRUE if the entity has any form controllers, FALSE otherwise.
   */
  protected function isValidEntity($entity_type) {
    return $this->entityTypes[$entity_type]->hasFormClasses();
  }

}
