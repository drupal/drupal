<?php

/**
 * @file
 * Contains \Drupal\entity_test\Controller\EntityTestController.
 */

namespace Drupal\entity_test\Controller;

use Drupal\Core\Entity\EntityInterface;

/**
 * Controller routines for entity_test routes.
 */
class EntityTestController {

  /**
   * @todo Remove entity_test_add()
   */
  public function testAdd($entity_type) {
    return entity_test_add($entity_type);
  }

  /**
   * @todo Remove entity_test_edit()
   */
  public function testEdit(EntityInterface $entity) {
    return entity_test_edit($entity);
  }

}
