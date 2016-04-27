<?php

namespace Drupal\Core\Entity\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Defines a generic controller to list entities.
 */
class EntityListController extends ControllerBase {

  /**
   * Provides the listing page for any entity type.
   *
   * @param string $entity_type
   *   The entity type to render.
   *
   * @return array
   *   A render array as expected by drupal_render().
   */
  public function listing($entity_type) {
    return $this->entityManager()->getListBuilder($entity_type)->render();
  }

}
