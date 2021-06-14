<?php

namespace Drupal\field_ui;

/**
 * Defines a class to build a listing of form mode entities.
 *
 * @see \Drupal\Core\Entity\Entity\EntityFormMode
 */
class EntityFormModeListBuilder extends EntityDisplayModeListBuilder {

  /**
   * Filters entities based on their form mode handlers.
   *
   * @param $entity_type
   *   The entity type of the entity that needs to be validated.
   *
   * @return bool
   *   TRUE if the entity has any forms, FALSE otherwise.
   */
  protected function isValidEntity($entity_type) {
    return $this->entityTypes[$entity_type]->hasFormClasses();
  }

}
