<?php

/**
 * @file
 * Contains \Drupal\entity_test\Routing\RouteSubscriber.
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
    $types = entity_test_entity_types();
    $types[] = 'entity_test_string_id';
    $types[] = 'entity_test_no_id';

    $routes = array();
    foreach ($types as $entity_type) {
      $routes["entity_test.add_$entity_type"] = new Route(
        "$entity_type/add",
        array('_content' => '\Drupal\entity_test\Controller\EntityTestController::testAdd', 'entity_type' => $entity_type),
        array('_permission' => 'administer entity_test content')
      );

      $routes["entity_test.edit_$entity_type"] = new Route(
        "$entity_type/manage/{" . $entity_type . '}',
        array('_content' => '\Drupal\entity_test\Controller\EntityTestController::testEdit', '_entity_type' => $entity_type),
        array('_permission' => 'administer entity_test content'),
        array('parameters' => array(
          $entity_type => array('type' => 'entity:' . $entity_type),
        ))
      );

      $routes["entity_test.admin_$entity_type"] = new Route(
        "$entity_type/structure/{bundle}",
        array('_content' => '\Drupal\entity_test\Controller\EntityTestController::testAdmin'),
        array('_permission' => 'administer entity_test content')
      );
    }
    return $routes;
  }

}
