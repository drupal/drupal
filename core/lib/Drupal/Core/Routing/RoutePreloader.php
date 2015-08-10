<?php

/**
 * @file
 * Contains \Drupal\Core\Routing\RoutePreloader.
 */

namespace Drupal\Core\Routing;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Defines a class which preloads non-admin routes.
 *
 * On an actual site we want to avoid too many database queries so we build a
 * list of all routes which most likely appear on the actual site, which are all
 * HTML routes not starting with "/admin".
 */
class RoutePreloader implements EventSubscriberInterface {

  /**
   * The route provider.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface|\Drupal\Core\Routing\PreloadableRouteProviderInterface
   */
  protected $routeProvider;

  /**
   * The state key value store.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Contains the non-admin routes while rebuilding the routes.
   *
   * @var array
   */
  protected $nonAdminRoutesOnRebuild = array();

  /**
   * The cache backend used to skip the state loading.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Constructs a new RoutePreloader.
   *
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The route provider.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state key value store.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   */
  public function __construct(RouteProviderInterface $route_provider, StateInterface $state, CacheBackendInterface $cache) {
    $this->routeProvider = $route_provider;
    $this->state = $state;
    $this->cache = $cache;
  }

  /**
   * Loads all non-admin routes right before the actual page is rendered.
   *
   * @param \Symfony\Component\HttpKernel\Event\KernelEvent $event
   *   The event to process.
   */
  public function onRequest(KernelEvent $event) {
    // Only preload on normal HTML pages, as they will display menu links.
    if ($this->routeProvider instanceof PreloadableRouteProviderInterface && $event->getRequest()->getRequestFormat() == 'html') {

      // Ensure that the state query is cached to skip the database query, if
      // possible.
      $key = 'routing.non_admin_routes';
      if ($cache = $this->cache->get($key)) {
        $routes = $cache->data;
      }
      else {
        $routes = $this->state->get($key, []);
        $this->cache->set($key, $routes, Cache::PERMANENT, ['routes']);
      }

      if ($routes) {
        // Preload all the non-admin routes at once.
        $this->routeProvider->preLoadRoutes($routes);
      }
    }
  }

  /**
   * Alters existing routes for a specific collection.
   *
   * @param \Drupal\Core\Routing\RouteBuildEvent $event
   *   The route build event.
   */
  public function onAlterRoutes(RouteBuildEvent $event) {
    $collection = $event->getRouteCollection();
    foreach ($collection->all() as $name => $route) {
      if (strpos($route->getPath(), '/admin/') !== 0 && $route->getPath() != '/admin') {
        $this->nonAdminRoutesOnRebuild[] = $name;
      }
    }
    $this->nonAdminRoutesOnRebuild = array_unique($this->nonAdminRoutesOnRebuild);
  }

  /**
   * Store the non admin routes in state when the route building is finished.
   *
   * @param \Symfony\Component\EventDispatcher\Event $event
   *   The route finish event.
   */
  public function onFinishedRoutes(Event $event) {
    $this->state->set('routing.non_admin_routes', $this->nonAdminRoutesOnRebuild);
    $this->nonAdminRoutesOnRebuild = array();
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Set a really low priority to catch as many as possible routes.
    $events[RoutingEvents::ALTER] = array('onAlterRoutes', -1024);
    $events[RoutingEvents::FINISHED] = array('onFinishedRoutes');
    // Load the routes before the controller is executed (which happens after
    // the kernel request event).
    $events[KernelEvents::REQUEST][] = array('onRequest');
    return $events;
  }

}
