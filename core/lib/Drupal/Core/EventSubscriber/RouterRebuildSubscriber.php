<?php

/**
 * @file
 * Contains \Drupal\Core\EventSubscriber\RouterRebuildSubscriber.
 */

namespace Drupal\Core\EventSubscriber;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Routing\RouteBuilderInterface;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Rebuilds the router and menu_router if necessary.
 */
class RouterRebuildSubscriber implements EventSubscriberInterface {

  /**
   * @var \Drupal\Core\Routing\RouteBuilderInterface
   */
  protected $routeBuilder;

  /**
   * Constructs the RouterRebuildSubscriber object.
   *
   * @param \Drupal\Core\Routing\RouteBuilderInterface $route_builder
   *   The route builder.
   */
  public function __construct(RouteBuilderInterface $route_builder) {
    $this->routeBuilder = $route_builder;
  }

  /**
   * Rebuilds routers if necessary.
   *
   * @param \Symfony\Component\HttpKernel\Event\PostResponseEvent $event
   *   The event object.
   */
  public function onKernelTerminate(PostResponseEvent $event) {
    $this->routeBuilder->rebuildIfNeeded();
  }

  /**
   * Rebuilds the menu_router and deletes the local_task cache tag.
   *
   * @param \Symfony\Component\EventDispatcher\Event $event
   *   The event object.
   */
  public function onRouterRebuild(Event $event) {
    menu_router_rebuild();
    Cache::deleteTags(array('local_task' => 1));
  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::TERMINATE][] = array('onKernelTerminate', 200);
    $events[RoutingEvents::FINISHED][] = array('onRouterRebuild', 200);
    return $events;
  }

}
