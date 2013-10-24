<?php

/**
 * @file
 * Contains \Drupal\entity_test\Controller\EntityTestController.
 */

namespace Drupal\entity_test\Controller;

use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\HttpFoundation\Request;

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
  public function testEdit(Request $request) {
    $entity = $request->attributes->get($request->attributes->get('_entity_type'));
    return entity_test_edit($entity);
  }

}
