<?php

namespace Drupal\media_library\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for media library routes.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Add the media library UI access checks to the widget displays of the
    // media library view.
    if ($route = $collection->get('view.media_library.widget')) {
      $route->addRequirements(['_custom_access' => 'media_library.ui_builder:checkAccess']);
    }
    if ($route = $collection->get('view.media_library.widget_table')) {
      $route->addRequirements(['_custom_access' => 'media_library.ui_builder:checkAccess']);
    }
  }

}
