<?php

/**
 * @file
 * Contains \Drupal\entity_test\Routing\EntityTestRoutes.
 */

namespace Drupal\entity_test\Routing;

use Symfony\Component\Routing\Route;

/**
 * Subscriber for Entity Test routes.
 */
class EntityTestRoutes {

  /**
   * Returns an array of route objects.
   *
   * @return \Symfony\Component\Routing\Route[]
   *   An array of route objects.
   */
  public function routes() {
    $types = entity_test_entity_types(ENTITY_TEST_TYPES_ROUTING);
    $types[] = 'entity_test_string_id';
    $types[] = 'entity_test_no_id';

    $routes = array();
    foreach ($types as $entity_type_id) {
      $routes["entity.$entity_type_id.add_form"] = new Route(
        "$entity_type_id/add",
        array('_controller' => '\Drupal\entity_test\Controller\EntityTestController::testAdd', 'entity_type_id' => $entity_type_id),
        array('_permission' => 'administer entity_test content')
      );

      $routes["entity.$entity_type_id.canonical"] = new Route(
        $entity_type_id . '/manage/{' . $entity_type_id . '}',
        array('_controller' => '\Drupal\entity_test\Controller\EntityTestController::testEdit', 'entity_type_id' => $entity_type_id),
        array('_permission' => 'administer entity_test content'),
        array('parameters' => array(
          $entity_type_id => array('type' => 'entity:' . $entity_type_id),
        ))
      );

      $routes["entity.$entity_type_id.edit_form"] = new Route(
        $entity_type_id . '/manage/{' . $entity_type_id . '}',
        array('_controller' => '\Drupal\entity_test\Controller\EntityTestController::testEdit', 'entity_type_id' => $entity_type_id),
        array('_permission' => 'administer entity_test content'),
        array('parameters' => array(
          $entity_type_id => array('type' => 'entity:' . $entity_type_id),
        ))
      );

      $routes["entity.$entity_type_id.delete_form"] = new Route(
        'entity_test/delete/' . $entity_type_id . '/{' . $entity_type_id . '}',
        array('_entity_form' => $entity_type_id . '.delete'),
        array('_permission' => 'administer entity_test content')
      );

      $routes["entity.$entity_type_id.admin_form"] = new Route(
        "$entity_type_id/structure/{bundle}",
        array('_controller' => '\Drupal\entity_test\Controller\EntityTestController::testAdmin'),
        array('_permission' => 'administer entity_test content')
      );
    }
    return $routes;
  }

}
