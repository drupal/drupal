<?php

/**
 * @file
 * Contains \Drupal\system\EventSubscriber\AdminRouteSubscriber
 */

namespace Drupal\system\EventSubscriber;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Adds the _admin_route option to each admin route.
 */
class AdminRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection, $provider) {
    foreach ($collection->all() as $route) {
      if (strpos($route->getPath(), '/admin') === 0 && !$route->hasOption('_admin_route')) {
        $route->setOption('_admin_route', TRUE);
      }
    }
  }

}
