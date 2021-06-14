<?php

namespace Drupal\system\EventSubscriber;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Adds the _admin_route option to each admin HTML route.
 */
class AdminRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    foreach ($collection->all() as $route) {
      $path = $route->getPath();
      if (($path == '/admin' || strpos($path, '/admin/') === 0) && !$route->hasOption('_admin_route') && static::isHtmlRoute($route)) {
        $route->setOption('_admin_route', TRUE);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = parent::getSubscribedEvents();

    // Use a lower priority than \Drupal\field_ui\Routing\RouteSubscriber or
    // \Drupal\views\EventSubscriber\RouteSubscriber to ensure we add the option
    // to their routes.
    $events[RoutingEvents::ALTER] = ['onAlterRoutes', -200];

    return $events;
  }

  /**
   * Determines whether the given route is an HTML route.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to analyze.
   *
   * @return bool
   *   TRUE if HTML is a valid format for this route.
   */
  protected static function isHtmlRoute(Route $route) {
    // If a route has no explicit format, then HTML is valid.
    $format = $route->hasRequirement('_format') ? explode('|', $route->getRequirement('_format')) : ['html'];
    return in_array('html', $format, TRUE);
  }

}
