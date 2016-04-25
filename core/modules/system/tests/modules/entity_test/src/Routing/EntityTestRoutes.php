<?php

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

    $routes = array();
    foreach ($types as $entity_type_id) {
      $routes["entity.$entity_type_id.admin_form"] = new Route(
        "$entity_type_id/structure/{bundle}",
        array('_controller' => '\Drupal\entity_test\Controller\EntityTestController::testAdmin'),
        array('_permission' => 'administer entity_test content'),
        array('_admin_route' => TRUE)
      );
    }
    return $routes;
  }

}
