<?php

namespace Drupal\Core\Routing;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\RouteCollection;
use Drupal\Core\Database\Connection;

// cspell:ignore filesort

/**
 * A Route Provider front-end for all Drupal-stored routes.
 */
class RouteProvider implements CacheableRouteProviderInterface, PreloadableRouteProviderInterface, EventSubscriberInterface {

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
  protected $routes = [];

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
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Cache ID prefix used to load routes.
   */
  const ROUTE_LOAD_CID_PREFIX = 'route_provider.route_load:';

  /**
   * An array of cache key parts to be used for the route match cache.
   *
   * @var string[]
   */
  protected $extraCacheKeyParts = [];

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
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   (Optional) The language manager.
   */
  public function __construct(Connection $connection, StateInterface $state, CurrentPathStack $current_path, CacheBackendInterface $cache_backend, InboundPathProcessorInterface $path_processor, CacheTagsInvalidatorInterface $cache_tag_invalidator, $table = 'router', ?LanguageManagerInterface $language_manager = NULL) {
    $this->connection = $connection;
    $this->state = $state;
    $this->currentPath = $current_path;
    $this->cache = $cache_backend;
    $this->cacheTagInvalidator = $cache_tag_invalidator;
    $this->pathProcessor = $path_processor;
    $this->tableName = $table;
    $this->languageManager = $language_manager ?: \Drupal::languageManager();
  }

  /**
   * Finds routes that may potentially match the request.
   *
   * This may return a mixed list of class instances, but all routes returned
   * must extend the core symfony route. The classes may also implement
   * RouteObjectInterface to link to a content document.
   *
   * This method may not throw an exception based on implementation specific
   * restrictions on the URL. That case is considered a not found - returning
   * an empty array. Exceptions are only used to abort the whole request in
   * case something is seriously broken, like the storage backend being down.
   *
   * Note that implementations may not implement an optimal matching
   * algorithm, simply a reasonable first pass.  That allows for potentially
   * very large route sets to be filtered down to likely candidates, which
   * may then be filtered in memory more completely.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   A request against which to match.
   *
   * @return \Symfony\Component\Routing\RouteCollection
   *   RouteCollection with all URLs that could potentially match $request.
   *   Empty collection if nothing can match. The collection will be sorted from
   *   highest to lowest fit (match of path parts) and then in ascending order
   *   by route name for routes with the same fit.
   */
  public function getRouteCollectionForRequest(Request $request) {
    // Cache both the system path as well as route parameters and matching
    // routes.
    $cid = $this->getRouteCollectionCacheId($request);
    if ($cached = $this->cache->get($cid)) {
      $this->currentPath->setPath($cached->data['path'], $request);
      $request->query->replace($cached->data['query']);
      if ($cached->data['routes'] === FALSE) {
        return new RouteCollection();
      }
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
        'routes' => $routes->count() === 0 ? FALSE : $routes,
      ];
      $this->cache->set($cid, $cache_value, CacheBackendInterface::CACHE_PERMANENT, ['route_match']);
      return $routes;
    }
  }

  /**
   * Find the route using the provided route name.
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
    $routes = $this->getRoutesByNames([$name]);
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
        try {
          $result = $this->connection->query('SELECT [name], [route] FROM {' . $this->connection->escapeTable($this->tableName) . '} WHERE [name] IN ( :names[] )', [':names[]' => $routes_to_load]);
          $routes = $result->fetchAllKeyed();

          $this->cache->set($cid, $routes, Cache::PERMANENT, ['routes']);
        }
        catch (\Exception $e) {
          $routes = [];
        }
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
  protected function getCandidateOutlines(array $parts) {
    $number_parts = count($parts);
    $ancestors = [];
    $length = $number_parts - 1;
    $end = (1 << $number_parts) - 1;

    // The highest possible mask is a 1 bit for every part of the path. We will
    // check every value down from there to generate a possible outline.
    if ($number_parts == 1) {
      $masks = [1];
    }
    elseif ($number_parts <= 3 && $number_parts > 0) {
      // Optimization - don't query the state system for short paths. This also
      // insulates against the state entry for masks going missing for common
      // user-facing paths since we generate all values without checking state.
      $masks = range($end, 1);
    }
    elseif ($number_parts <= 0) {
      // No path can match, short-circuit the process.
      $masks = [];
    }
    else {
      // Get the actual patterns that exist out of state.
      $masks = (array) $this->state->get('routing.menu_masks.' . $this->tableName, []);
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
          // Bit zero means wildcard.
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
   *   The route pattern to search for.
   *
   * @return \Symfony\Component\Routing\RouteCollection
   *   Returns a route collection of matching routes. The collection may be
   *   empty and will be sorted from highest to lowest fit (match of path parts)
   *   and then in ascending order by route name for routes with the same fit.
   */
  protected function getRoutesByPath($path) {
    // Split the path up on the slashes, ignoring multiple slashes in a row
    // or leading or trailing slashes. Convert to lower case here so we can
    // have a case-insensitive match from the incoming path to the lower case
    // pattern outlines from \Drupal\Core\Routing\RouteCompiler::compile().
    // @see \Drupal\Core\Routing\CompiledRoute::__construct()
    $parts = preg_split('@/+@', mb_strtolower($path), -1, PREG_SPLIT_NO_EMPTY);

    $collection = new RouteCollection();

    $ancestors = $this->getCandidateOutlines($parts);
    if (empty($ancestors)) {
      return $collection;
    }

    // The >= check on number_parts allows us to match routes with optional
    // trailing wildcard parts as long as the pattern matches, since we
    // dump the route pattern without those optional parts.
    try {
      $routes = $this->connection->query("SELECT [name], [route], [fit] FROM {" . $this->connection->escapeTable($this->tableName) . "} WHERE [pattern_outline] IN ( :patterns[] ) AND [number_parts] >= :count_parts", [
        ':patterns[]' => $ancestors,
        ':count_parts' => count($parts),
      ])
        ->fetchAll(\PDO::FETCH_ASSOC);
    }
    catch (\Exception $e) {
      $routes = [];
    }

    // We sort by fit and name in PHP to avoid a SQL filesort and avoid any
    // difference in the sorting behavior of SQL back-ends.
    usort($routes, [$this, 'routeProviderRouteCompare']);

    foreach ($routes as $row) {
      $collection->add($row['name'], unserialize($row['route']));
    }

    return $collection;
  }

  /**
   * Comparison function for usort on routes.
   */
  protected function routeProviderRouteCompare(array $a, array $b) {
    if ($a['fit'] == $b['fit']) {
      return strcmp($a['name'], $b['name']);
    }
    // Reverse sort from highest to lowest fit. PHP should cast to int, but
    // the explicit cast makes this sort more robust against unexpected input.
    return (int) $b['fit'] <=> (int) $a['fit'];
  }

  /**
   * {@inheritdoc}
   */
  public function getAllRoutes() {
    $select = $this->connection->select($this->tableName, 'router')
      ->fields('router', ['name', 'route']);
    $routes = $select->execute()->fetchAllKeyed();

    $result = [];
    foreach ($routes as $name => $route) {
      $result[$name] = unserialize($route);
    }

    $array_object = new \ArrayObject($result);
    return $array_object->getIterator();
  }

  /**
   * {@inheritdoc}
   */
  public function reset() {
    $this->routes = [];
    $this->serializedRoutes = [];
    $this->cacheTagInvalidator->invalidateTags(['routes']);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[RoutingEvents::FINISHED][] = ['reset'];
    return $events;
  }

  /**
   * {@inheritdoc}
   */
  public function addExtraCacheKeyPart($cache_key_provider, $cache_key_part) {
    $this->extraCacheKeyParts[$cache_key_provider] = $cache_key_part;
  }

  /**
   * Returns the cache ID for the route collection cache.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return string
   *   The cache ID.
   */
  protected function getRouteCollectionCacheId(Request $request) {
    // Include the current language code in the cache identifier as
    // the language information can be elsewhere than in the path, for example
    // based on the domain.
    $this->addExtraCacheKeyPart('language', $this->getCurrentLanguageCacheIdPart());

    $this->addExtraCacheKeyPart('query_parameters', $this->getQueryParametersCacheIdPart($request));

    // Sort the cache key parts by their provider in order to have predictable
    // cache keys.
    ksort($this->extraCacheKeyParts);
    $key_parts = [];
    foreach ($this->extraCacheKeyParts as $provider => $key_part) {
      $key_parts[] = '[' . $provider . ']=' . $key_part;
    }

    return 'route:' . implode(':', $key_parts) . ':' . $request->getPathInfo();
  }

  /**
   * Returns the query parameters identifier for the route collection cache.
   *
   * The query parameters on the request may be altered programmatically, e.g.
   * while serving private files or in subrequests. As such, we must vary on
   * both the query string from the client and the parameter bag after incoming
   * route processors have modified the request object.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request.
   *
   * @return string
   */
  protected function getQueryParametersCacheIdPart(Request $request) {
    // @todo Use \Symfony\Component\HttpFoundation\Request::normalizeQueryString
    //   for recursive key ordering if support is added in the future.
    $recursive_sort = function (&$array) use (&$recursive_sort) {
      foreach ($array as &$v) {
        if (is_array($v)) {
          $recursive_sort($v);
        }
      }
      ksort($array);
    };
    // Recursively normalize the query parameters to ensure maximal cache hits.
    // If we did not normalize the order, functionally identical query string
    // sets could be sent in differing order creating a potential DoS vector
    // and decreasing cache hit rates.
    $sorted_resolved_parameters = $request->query->all();
    $recursive_sort($sorted_resolved_parameters);
    $sorted_original_parameters = Request::create('/?' . $request->getQueryString())->query->all();
    $recursive_sort($sorted_original_parameters);
    // Hash this portion to help shorten the total key length.
    $resolved_hash = $sorted_resolved_parameters
      ? sha1(http_build_query($sorted_resolved_parameters))
      : NULL;
    return implode(
      ',',
      array_filter([
        http_build_query($sorted_original_parameters),
        $resolved_hash,
      ])
    );
  }

  /**
   * Returns the language identifier for the route collection cache.
   *
   * @return string
   *   The language identifier.
   */
  protected function getCurrentLanguageCacheIdPart() {
    // This must be in sync with the language logic in
    // \Drupal\path_alias\PathProcessor\AliasPathProcessor::processInbound() and
    // \Drupal\path_alias\AliasManager::getPathByAlias().
    // @todo Update this if necessary in https://www.drupal.org/node/1125428.
    return $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_URL)->getId();
  }

}
