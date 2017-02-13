<?php

namespace Drupal\entity_test_hooks\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\entity_test\Entity\EntityTest;

/**
 * Controller to view entities.
 */
class ViewController extends ControllerBase {

  /**
   * View an entity using \Drupal\Core\Entity\EntityViewBuilder::view()
   *
   * @param \Drupal\entity_test\Entity\EntityTest $entity_test
   *   The entity to be viewed.
   *
   * @return array $build
   *   The rendered entity
   */
  public function view(EntityTest $entity_test) {
    return $this->entityTypeManager()->getViewBuilder('entity_test')->view($entity_test);
  }

  /**
   * View an entity using \Drupal\Core\Entity\EntityViewBuilder::viewMultiple()
   *
   * @param \Drupal\entity_test\Entity\EntityTest $entity_test
   *   The entity to be viewed.
   *
   * @return array $build
   *   The rendered entity
   */
  public function viewMultiple(EntityTest $entity_test) {
    return $this->entityTypeManager()->getViewBuilder('entity_test')->viewMultiple([$entity_test]);
  }
}
