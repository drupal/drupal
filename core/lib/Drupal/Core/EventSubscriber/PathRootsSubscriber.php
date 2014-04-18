<?php

/**
 * @file
 * Contains \Drupal\Core\EventSubscriber\PathRootsSubscriber.
 */

namespace Drupal\Core\EventSubscriber;

use Drupal\Core\State\StateInterface;
use Drupal\Core\Routing\RouteBuildEvent;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\RouteCollection;

/**
 * Provides all available first bits of all route paths.
 */
class PathRootsSubscriber implements EventSubscriberInterface {

  /**
   * Stores the path roots available in the router.
   *
   * A path root is the first virtual directory of a path, like 'admin', 'node'
   * or 'user'.
   *
   * @var array
   */
  protected $pathRoots;

  /**
   * The state key value store.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs a new PathRootsSubscriber instance.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state key value store.
   */
  public function __construct(StateInterface $state) {
    $this->state = $state;
  }

  /**
   * Collects all path roots.
   *
   * @param \Drupal\Core\Routing\RouteBuildEvent $event
   *   The route build event.
   */
  public function onRouteAlter(RouteBuildEvent $event) {
    $collection = $event->getRouteCollection();
    foreach ($collection->all() as $route) {
      $bits = explode('/', ltrim($route->getPath(), '/'));
      $this->pathRoots[$bits[0]] = $bits[0];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onRouteFinished() {
    $this->state->set('router.path_roots', array_keys($this->pathRoots));
    unset($this->pathRoots);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = array();
    // Try to set a low priority to ensure that all routes are already added.
    $events[RoutingEvents::ALTER][] = array('onRouteAlter', -1024);
    $events[RoutingEvents::FINISHED][] = array('onRouteFinished');
    return $events;
  }

}
