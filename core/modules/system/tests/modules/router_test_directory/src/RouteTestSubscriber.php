<?php

/**
 * Definition of \Drupal\router_test\RouteTestSubscriber.
 */

namespace Drupal\router_test;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route event and add a test route.
 */
class RouteTestSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    $route = $collection->get('router_test.6');
    // Change controller method from test1 to test5.
    $route->setDefault('_controller', '\Drupal\router_test\TestControllers::test5');
  }

}
