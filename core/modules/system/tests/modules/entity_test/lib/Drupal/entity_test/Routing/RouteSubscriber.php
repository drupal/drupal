<?php

/**
 * @file
 * Contains \Drupal\entity_test\Routing\RouteSubscriber.
 */

namespace Drupal\entity_test\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for Entity Test routes.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function routes(RouteCollection $collection) {
    $types = entity_test_entity_types();
    $types[] = 'entity_test_render';

    foreach ($types as $entity_type) {
      $route = new Route(
        "$entity_type/add",
        array('_content' => '\Drupal\entity_test\Controller\EntityTestController::testAdd', 'entity_type' => $entity_type),
        array('_permission' => 'administer entity_test content')
      );
      $collection->add("entity_test.add_$entity_type", $route);

      $route = new Route(
        "$entity_type/manage/{entity}",
        array('_content' => '\Drupal\entity_test\Controller\EntityTestController::testEdit'),
        array('_permission' => 'administer entity_test content'),
        array('parameters' => array(
          'entity' => array('type' => 'entity:' . $entity_type),
        ))
      );
      $collection->add("entity_test.edit_$entity_type", $route);
    }
  }

}
