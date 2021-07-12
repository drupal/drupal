<?php

namespace Drupal\Core\Routing;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Route;

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
  protected $nonAdminRoutesOnRebuild = [];

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
   *   The cache backend.
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
      if (strpos($route->getPath(), '/admin/') !== 0 && $route->getPath() != '/admin' && static::isGetAndHtmlRoute($route)) {
        $this->nonAdminRoutesOnRebuild[] = $name;
      }
    }
    $this->nonAdminRoutesOnRebuild = array_unique($this->nonAdminRoutesOnRebuild);
  }

  /**
   * Store the non admin routes in state when the route building is finished.
   */
  public function onFinishedRoutes() {
    $this->state->set('routing.non_admin_routes', $this->nonAdminRoutesOnRebuild);
    $this->nonAdminRoutesOnRebuild = [];
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Set a really low priority to catch as many as possible routes.
    $events[RoutingEvents::ALTER] = ['onAlterRoutes', -1024];
    $events[RoutingEvents::FINISHED] = ['onFinishedRoutes'];
    // Load the routes before the controller is executed (which happens after
    // the kernel request event).
    $events[KernelEvents::REQUEST][] = ['onRequest'];
    return $events;
  }

  /**
   * Determines whether the given route is a GET and HTML route.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to analyze.
   *
   * @return bool
   *   TRUE if GET is a valid method and HTML is a valid format for this route.
   */
  protected static function isGetAndHtmlRoute(Route $route) {
    $methods = $route->getMethods() ?: ['GET'];
    // If a route has no explicit format, then HTML is valid.
    // @see \Drupal\Core\Routing\RequestFormatRouteFilter::getAvailableFormats()
    $format = $route->hasRequirement('_format') ? explode('|', $route->getRequirement('_format')) : ['html'];
    return in_array('GET', $methods, TRUE) && in_array('html', $format, TRUE);
  }

}
