<?php

/**
 * @file
 * Contains \Drupal\dblog\Routing\RouteSubscriber.
 */

namespace Drupal\dblog\Routing;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\RouteBuildEvent;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Route;

/**
 * Provides dynamic routes for dblog.
 */
class RouteSubscriber implements EventSubscriberInterface {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Creates a new RouteSubscriber.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[RoutingEvents::DYNAMIC] = 'routes';
    return $events;
  }

  /**
   * Generate dynamic routes for various dblog pages.
   *
   * @param \Drupal\Core\Routing\RouteBuildEvent $event
   *   The route building event.
   *
   * @return \Symfony\Component\Routing\RouteCollection
   *   The route collection that contains the new dynamic route.
   */
  public function routes(RouteBuildEvent $event) {
    $collection = $event->getRouteCollection();
    if ($this->moduleHandler->moduleExists('search')) {
      // The block entity listing page.
      $route = new Route(
        'admin/reports/search',
        array(
          '_content' => '\Drupal\dblog\Controller\DbLogController::search',
          '_title' => 'Top search phrases',
        ),
        array(
          '_permission' => 'access site reports',
        )
      );
      $collection->add('dblog.search', $route);
    }
  }

}
