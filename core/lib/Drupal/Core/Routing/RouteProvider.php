<?php

/**
 * @file
 * Contains \Drupal\Core\Routing\RouteProvider.
 */

namespace Drupal\Core\Routing;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Cmf\Component\Routing\PagedRouteCollection;
use Symfony\Cmf\Component\Routing\PagedRouteProviderInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

use \Drupal\Core\Database\Connection;

/**
 * A Route Provider front-end for all Drupal-stored routes.
 */
class RouteProvider implements PreloadableRouteProviderInterface, PagedRouteProviderInterface, EventSubscriberInterface {

  /**
   * The database connection from which to read route information.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The name of the SQL table from which to read the routes.
   *
   * @var string
   */
  protected $tableName;

  /**
   * The state.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * A cache of already-loaded routes, keyed by route name.
   *
   * @var \Symfony\Component\Routing\Route[]
   */
  protected $routes = array();

  /**
   * A cache of already-loaded serialized routes, keyed by route name.
   *
   * @var string[]
   */
  protected $serializedRoutes = [];

  /**
   * The current path.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The cache tag invalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $cacheTagInvalidator;

  /**
   * A path processor manager for resolving the system path.
   *
   * @var \Drupal\Core\PathProcessor\InboundPathProcessorInterface
   */
  protected $pathProcessor;

  /**
   * Cache ID prefix used to load routes.
   */
  const ROUTE_LOAD_CID_PREFIX = 'route_provider.route_load:';

  /**
   * Constructs a new PathMatcher.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   A database connection object.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state.
   * @param \Drupal\Core\Path\CurrentPathStack $current_path
   *   The current path.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   * @param \Drupal\Core\PathProcessor\InboundPathProcessorInterface $path_processor
   *   The path processor.
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cache_tag_invalidator
   *   The cache tag invalidator.
   * @param string $table
   *   (Optional) The table in the database to use for matching. Defaults to 'router'
   */
  public function __construct(Connection $connection, StateInterface $state, CurrentPathStack $current_path, CacheBackendInterface $cache_backend, InboundPathProcessorInterface $path_processor, CacheTagsInvalidatorInterface $cache_tag_invalidator, $table = 'router') {
    $this->connection = $connection;
    $this->state = $state;
    $this->currentPath = $current_path;
    $this->cache = $cache_backend;
    $this->cacheTagInvalidator = $cache_tag_invalidator;
    $this->pathProcessor = $path_processor;
    $this->tableName = $table;
  }

  /**
   * Finds routes that may potentially match the request.
   *
   * This may return a mixed list of class instances, but all routes returned
   * must extend the core symfony route. The classes may also implement
   * RouteObjectInterface to link to a content document.
   *
   * This method may not throw an exception based on implementation specific
   * restrictions on the url. That case is considered a not found - returning
   * an empty array. Exceptions are only used to abort the whole request in
   * case something is seriously broken, like the storage backend being down.
   *
   * Note that implementations may not implement an optimal matching
   * algorithm, simply a reasonable first pass.  That allows for potentially
   * very large route sets to be filtered down to likely candidates, which
   * may then be filtered in memory more completely.
   *
   * @param Request $request A request against which to match.
   *
   * @return \Symfony\Component\Routing\RouteCollection with all urls that
   *      could potentially match $request. Empty collection if nothing can
   *      match.
   */
  public function getRouteCollectionForRequest(Request $request) {
    // Cache both the system path as well as route parameters and matching
    // routes.
    $cid = 'route:' . $request->getPathInfo() . ':' .  $request->getQueryString();
    if ($cached = $this->cache->get($cid)) {
      $this->currentPath->setPath($cached->data['path'], $request);
      $request->query->replace($cached->data['query']);
      return $cached->data['routes'];
    }
    else {
      // Just trim on the right side.
      $path = $request->getPathInfo();
      $path = $path === '/' ? $path : rtrim($request->getPathInfo(), '/');
      $path = $this->pathProcessor->processInbound($path, $request);
      $this->currentPath->setPath($path, $request);
      // Incoming path processors may also set query parameters.
      $query_parameters = $request->query->all();
      $routes = $this->getRoutesByPath(rtrim($path, '/'));
      $cache_value = [
        'path' => $path,
        'query' => $query_parameters,
        'routes' => $routes,
      ];
      $this->cache->set($cid, $cache_value, CacheBackendInterface::CACHE_PERMANENT, ['route_match']);
      return $routes;
    }
  }

  /**
   * Find the route using the provided route name (and parameters).
   *
   * @param string $name
   *   The route name to fetch
   *
   * @return \Symfony\Component\Routing\Route
   *   The found route.
   *
   * @throws \Symfony\Component\Routing\Exception\RouteNotFoundException
   *   Thrown if there is no route with that name in this repository.
   */
  public function getRouteByName($name) {
    $routes = $this->getRoutesByNames(array($name));
    if (empty($routes)) {
      throw new RouteNotFoundException(sprintf('Route "%s" does not exist.', $name));
    }

    return reset($routes);
  }

  /**
   * {@inheritdoc}
   */
  public function preLoadRoutes($names) {
    if (empty($names)) {
      throw new \InvalidArgumentException('You must specify the route names to load');
    }

    $routes_to_load = array_diff($names, array_keys($this->routes), array_keys($this->serializedRoutes));
    if ($routes_to_load) {

      $cid = static::ROUTE_LOAD_CID_PREFIX . hash('sha512', serialize($routes_to_load));
      if ($cache = $this->cache->get($cid)) {
        $routes = $cache->data;
      }
      else {
        $result = $this->connection->query('SELECT name, route FROM {' . $this->connection->escapeTable($this->tableName) . '} WHERE name IN ( :names[] )', array(':names[]' => $routes_to_load));
        $routes = $result->fetchAllKeyed();

        $this->cache->set($cid, $routes, Cache::PERMANENT, ['routes']);
      }

      $this->serializedRoutes += $routes;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getRoutesByNames($names) {
    $this->preLoadRoutes($names);

    foreach ($names as $name) {
      // The specified route name might not exist or might be serialized.
      if (!isset($this->routes[$name]) && isset($this->serializedRoutes[$name])) {
        $this->routes[$name] = unserialize($this->serializedRoutes[$name]);
        unset($this->serializedRoutes[$name]);
      }
    }

    return array_intersect_key($this->routes, array_flip($names));
  }

  /**
   * Returns an array of path pattern outlines that could match the path parts.
   *
   * @param array $parts
   *   The parts of the path for which we want candidates.
   *
   * @return array
   *   An array of outlines that could match the specified path parts.
   */
  public function getCandidateOutlines(array $parts) {
    $number_parts = count($parts);
    $ancestors = array();
    $length = $number_parts - 1;
    $end = (1 << $number_parts) - 1;

    // The highest possible mask is a 1 bit for every part of the path. We will
    // check every value down from there to generate a possible outline.
    if ($number_parts == 1) {
      $masks = array(1);
    }
    elseif ($number_parts <= 3) {
      // Optimization - don't query the state system for short paths. This also
      // insulates against the state entry for masks going missing for common
      // user-facing paths since we generate all values without checking state.
      $masks = range($end, 1);
    }
    elseif ($number_parts <= 0) {
      // No path can match, short-circuit the process.
      $masks = array();
    }
    else {
      // Get the actual patterns that exist out of state.
      $masks = (array) $this->state->get('routing.menu_masks.' . $this->tableName, array());
    }


    // Only examine patterns that actually exist as router items (the masks).
    foreach ($masks as $i) {
      if ($i > $end) {
        // Only look at masks that are not longer than the path of interest.
        continue;
      }
      elseif ($i < (1 << $length)) {
        // We have exhausted the masks of a given length, so decrease the length.
        --$length;
      }
      $current = '';
      for ($j = $length; $j >= 0; $j--) {
        // Check the bit on the $j offset.
        if ($i & (1 << $j)) {
          // Bit one means the original value.
          $current .= $parts[$length - $j];
        }
        else {
          // Bit zero means means wildcard.
          $current .= '%';
        }
        // Unless we are at offset 0, add a slash.
        if ($j) {
          $current .= '/';
        }
      }
      $ancestors[] = '/' . $current;
    }
    return $ancestors;
  }

  /**
   * {@inheritdoc}
   */
  public function getRoutesByPattern($pattern) {
    $path = RouteCompiler::getPatternOutline($pattern);

    return $this->getRoutesByPath($path);
  }

  /**
   * Get all routes which match a certain pattern.
   *
   * @param string $path
   *   The route pattern to search for (contains % as placeholders).
   *
   * @return \Symfony\Component\Routing\RouteCollection
   *   Returns a route collection of matching routes.
   */
  protected function getRoutesByPath($path) {
    // Filter out each empty value, though allow '0' and 0, which would be
    // filtered out by empty().
    $parts = array_values(array_filter(explode('/', $path), function($value) {
      return $value !== NULL && $value !== '';
    }));

    $collection = new RouteCollection();

    $ancestors = $this->getCandidateOutlines($parts);
    if (empty($ancestors)) {
      return $collection;
    }

    $routes = $this->connection->query("SELECT name, route FROM {" . $this->connection->escapeTable($this->tableName) . "} WHERE pattern_outline IN ( :patterns[] ) ORDER BY fit DESC, name ASC", array(
      ':patterns[]' => $ancestors,
    ))
      ->fetchAllKeyed();

    foreach ($routes as $name => $route) {
      $route = unserialize($route);
      if (preg_match($route->compile()->getRegex(), $path, $matches)) {
        $collection->add($name, $route);
      }
    }

    return $collection;
  }

  /**
   * {@inheritdoc}
   */
  public function getAllRoutes() {
    return new PagedRouteCollection($this);
  }

  /**
   * {@inheritdoc}
   */
  public function reset() {
    $this->routes  = array();
    $this->serializedRoutes = array();
    $this->cacheTagInvalidator->invalidateTags(['routes']);
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[RoutingEvents::FINISHED][] = array('reset');
    return $events;
  }

  /**
   * {@inheritdoc}
   */
  public function getRoutesPaged($offset, $length = NULL) {
    $select = $this->connection->select($this->tableName, 'router')
      ->fields('router', ['name', 'route']);

    if (isset($length)) {
      $select->range($offset, $length);
    }

    $routes = $select->execute()->fetchAllKeyed();

    $result = [];
    foreach ($routes as $name => $route) {
      $result[$name] = unserialize($route);
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getRoutesCount() {
    return $this->connection->query("SELECT COUNT(*) FROM {" . $this->connection->escapeTable($this->tableName) . "}")->fetchField();
  }

}
