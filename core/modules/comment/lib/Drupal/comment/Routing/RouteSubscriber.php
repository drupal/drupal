<?php

/**
 * @file
 * Contains \Drupal\comment\Routing\RouteSubscriber.
 */

namespace Drupal\comment\Routing;

use Drupal\Core\Routing\RouteBuildEvent;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Route;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Defines a route subscriber for the comment module.
 */
class RouteSubscriber implements EventSubscriberInterface {

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a RouteSubscriber object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
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
   * If node module is present, adds the legacy /comment/{node}/reply route.
   *
   * @param \Drupal\Core\Routing\RouteBuildEvent $event
   *   The route build event.
   */
  public function routes(RouteBuildEvent $event) {
    $collection = $event->getRouteCollection();
    if ($this->moduleHandler->moduleExists('node')) {
      $route = new Route(
        "/comment/{node}/reply",
        array('_controller' => 'Drupal\comment\Controller\CommentController::redirectNode'),
        array('_entity_access' => 'node.view')
      );
      $collection->add('comment_node_redirect', $route);
    }
  }

}
