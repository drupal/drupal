<?php

/**
 * @file
 * Contains \Drupal\views\EventSubscriber\RouteSubscriber.
 */

namespace Drupal\views\EventSubscriber;

use Drupal\Core\Routing\RouteBuildEvent;
use Drupal\Core\Routing\RoutingEvents;
use Drupal\views\Plugin\views\display\DisplayRouterInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Builds up the routes of all views.
 */
class RouteSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[RoutingEvents::DYNAMIC] = 'dynamicRoutes';
    return $events;
  }

  /**
   * Adds routes defined by all views.
   *
   * @param \Drupal\Core\Routing\RouteBuildEvent $event
   *   The route building event.
   */
  public function dynamicRoutes(RouteBuildEvent $event) {
    $collection = $event->getRouteCollection();

    $views = views_get_applicable_views('uses_route');
    foreach ($views as $data) {
      list($view, $display_id) = $data;
      if ($view->setDisplay($display_id) && $display = $view->displayHandlers->get($display_id)) {
        if ($display instanceof DisplayRouterInterface) {
          $display->collectRoutes($collection);
        }
      }
      $view->destroy();
    }
  }

}
