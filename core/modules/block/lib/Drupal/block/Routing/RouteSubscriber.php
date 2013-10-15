<?php

/**
 * @file
 * Contains \Drupal\block\Routing\RouteSubscriber.
 */

namespace Drupal\block\Routing;

use Drupal\Core\Routing\RouteBuildEvent;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides dynamic routes for various block pages.
 */
class RouteSubscriber implements EventSubscriberInterface {

  /**
   * Implements EventSubscriberInterface::getSubscribedEvents().
   */
  public static function getSubscribedEvents() {
    $events[RoutingEvents::DYNAMIC] = 'routes';
    return $events;
  }

  /**
   * Generates dynamic routes for various block pages.
   *
   * @param \Drupal\Core\Routing\RouteBuildEvent $event
   *   The route building event.
   *
   * @return \Symfony\Component\Routing\RouteCollection
   *   The route collection that contains the new dynamic route.
   */
  public function routes(RouteBuildEvent $event) {
    $collection = $event->getRouteCollection();
    foreach (list_themes(TRUE) as $key => $theme) {
      // The block entity listing page.
      $route = new Route(
        "admin/structure/block/list/$key",
        array(
          '_controller' => '\Drupal\block\Controller\BlockListController::listing',
          'theme' => $key,
        ),
        array(
          '_access_theme' => 'TRUE',
          '_permission' => 'administer blocks',
        )
      );
      $collection->add("block.admin_display_$key", $route);
    }
  }

}
