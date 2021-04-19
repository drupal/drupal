<?php

namespace Drupal\media_test_embed\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('media.filter.preview')) {
      $route->setDefault('_controller', '\Drupal\media_test_embed\Controller\TestMediaFilterController::preview');
    }
  }

}
