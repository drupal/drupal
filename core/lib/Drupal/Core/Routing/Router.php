<?php

namespace Drupal\Core\Routing;

use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Routing\Enhancer\RouteEnhancerInterface;
use Symfony\Cmf\Component\Routing\LazyRouteCollection;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Cmf\Component\Routing\RouteProviderInterface as BaseRouteProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface as BaseUrlGeneratorInterface;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

/**
 * Router implementation in Drupal.
 *
 * A router determines, for an incoming request, the active controller, which is
 * a callable that creates a response.
 *
 * It consists of several steps, of which each are explained in more details
 * below:
 * 1. Get a collection of routes which potentially match the current request.
 *    This is done by the route provider. See ::getInitialRouteCollection().
 * 2. Filter the collection down further more. For example this filters out
 *    routes applying to other formats: See ::applyRouteFilters()
 * 3. Find the best matching route out of the remaining ones, by applying a
 *    regex. See ::matchCollection().
 * 4. Enhance the list of route attributes, for example loading entity objects.
 *    See ::applyRouteEnhancers().
 *
 * This implementation uses ideas of the following routers:
 * - \Symfony\Cmf\Component\Routing\DynamicRouter
 * - \Drupal\Core\Routing\UrlMatcher
 * - \Symfony\Cmf\Component\Routing\NestedMatcher\NestedMatcher
 *
 * @see \Symfony\Cmf\Component\Routing\DynamicRouter
 * @see \Drupal\Core\Routing\UrlMatcher
 * @see \Symfony\Cmf\Component\Routing\NestedMatcher\NestedMatcher
 */
class Router extends UrlMatcher implements RequestMatcherInterface, RouterInterface {

  /**
   * The route provider responsible for the first-pass match.
   *
   * @var \Symfony\Cmf\Component\Routing\RouteProviderInterface
   */
  protected $routeProvider;

  /**
   * The list of available enhancers.
   *
   * @var \Drupal\Core\Routing\EnhancerInterface[]
   */
  protected $enhancers = [];

  /**
   * The list of available route filters.
   *
   * @var \Drupal\Core\Routing\FilterInterface[]
   */
  protected $filters = [];

  /**
   * The URL generator.
   *
   * @var \Symfony\Component\Routing\Generator\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * Constructs a new Router.
   *
   * @param \Symfony\Cmf\Component\Routing\RouteProviderInterface $route_provider
   *   The route provider.
   * @param \Drupal\Core\Path\CurrentPathStack $current_path
   *   The current path stack.
   * @param \Symfony\Component\Routing\Generator\UrlGeneratorInterface $url_generator
   *   The URL generator.
   */
  public function __construct(BaseRouteProviderInterface $route_provider, CurrentPathStack $current_path, BaseUrlGeneratorInterface $url_generator) {
    parent::__construct($current_path);
    $this->routeProvider = $route_provider;
    $this->urlGenerator = $url_generator;
  }

  /**
   * Adds a route filter.
   *
   * @param \Drupal\Core\Routing\FilterInterface $route_filter
   *   The route filter.
   */
  public function addRouteFilter(FilterInterface $route_filter) {
    $this->filters[] = $route_filter;
  }

  /**
   * Adds a route enhancer.
   *
   * @param \Drupal\Core\Routing\EnhancerInterface $route_enhancer
   *   The route enhancer.
   */
  public function addRouteEnhancer(EnhancerInterface $route_enhancer) {
    $this->enhancers[] = $route_enhancer;
  }

  /**
   * {@inheritdoc}
   */
  public function match($pathinfo) {
    $request = Request::create($pathinfo);

    return $this->matchRequest($request);
  }

  /**
   * {@inheritdoc}
   */
  public function matchRequest(Request $request) {
    $collection = $this->getInitialRouteCollection($request);
    if ($collection->count() === 0) {
      throw new ResourceNotFoundException(sprintf('No routes found for "%s".', $this->currentPath->getPath()));
    }
    $collection = $this->applyRouteFilters($collection, $request);
    $collection = $this->applyFitOrder($collection);

    if ($ret = $this->matchCollection(rawurldecode($this->currentPath->getPath($request)), $collection)) {
      return $this->applyRouteEnhancers($ret, $request);
    }

    throw 0 < count($this->allow)
      ? new MethodNotAllowedException(array_unique($this->allow))
      : new ResourceNotFoundException(sprintf('No routes found for "%s".', $this->currentPath->getPath()));
  }

  /**
   * Tries to match a URL with a set of routes.
   *
   * @param string $pathinfo
   *   The path info to be parsed
   * @param \Symfony\Component\Routing\RouteCollection $routes
   *   The set of routes.
   *
   * @return array|null
   *   An array of parameters. NULL when there is no match.
   */
  protected function matchCollection($pathinfo, RouteCollection $routes) {
    // Try a case-sensitive match.
    $match = $this->doMatchCollection($pathinfo, $routes, TRUE);
    // Try a case-insensitive match.
    if ($match === NULL && $routes->count() > 0) {
      $match = $this->doMatchCollection($pathinfo, $routes, FALSE);
    }
    return $match;
  }

  /**
   * Tries to match a URL with a set of routes.
   *
   * This code is very similar to Symfony's UrlMatcher::matchCollection() but it
   * supports case-insensitive matching. The static prefix optimization is
   * removed as this duplicates work done by the query in
   * RouteProvider::getRoutesByPath().
   *
   * @param string $pathinfo
   *   The path info to be parsed
   * @param \Symfony\Component\Routing\RouteCollection $routes
   *   The set of routes.
   * @param bool $case_sensitive
   *   Determines if the match should be case-sensitive of not.
   *
   * @return array|null
   *   An array of parameters. NULL when there is no match.
   *
   * @see \Symfony\Component\Routing\Matcher\UrlMatcher::matchCollection()
   * @see \Drupal\Core\Routing\RouteProvider::getRoutesByPath()
   */
  protected function doMatchCollection($pathinfo, RouteCollection $routes, $case_sensitive) {
    foreach ($routes as $name => $route) {
      $compiledRoute = $route->compile();

      // Set the regex to use UTF-8.
      $regex = $compiledRoute->getRegex() . 'u';
      if (!$case_sensitive) {
        $regex = $regex . 'i';
      }
      if (!preg_match($regex, $pathinfo, $matches)) {
        continue;
      }

      $hostMatches = [];
      if ($compiledRoute->getHostRegex() && !preg_match($compiledRoute->getHostRegex(), $this->context->getHost(), $hostMatches)) {
        $routes->remove($name);
        continue;
      }

      // Check HTTP method requirement.
      if ($requiredMethods = $route->getMethods()) {
        // HEAD and GET are equivalent as per RFC.
        if ('HEAD' === $method = $this->context->getMethod()) {
          $method = 'GET';
        }

        if (!in_array($method, $requiredMethods)) {
          $this->allow = array_merge($this->allow, $requiredMethods);
          $routes->remove($name);
          continue;
        }
      }

      $status = $this->handleRouteRequirements($pathinfo, $name, $route);

      if (self::ROUTE_MATCH === $status[0]) {
        return $status[1];
      }

      if (self::REQUIREMENT_MISMATCH === $status[0]) {
        $routes->remove($name);
        continue;
      }

      return $this->getAttributes($route, $name, array_replace($matches, $hostMatches));
    }
  }

  /**
   * Returns a collection of potential matching routes for a request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Symfony\Component\Routing\RouteCollection
   *   The initial fetched route collection.
   */
  protected function getInitialRouteCollection(Request $request) {
    return $this->routeProvider->getRouteCollectionForRequest($request);
  }

  /**
   * Apply the route enhancers to the defaults, according to priorities.
   *
   * @param array $defaults
   *   The defaults coming from the final matched route.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return array
   *   The request attributes after applying the enhancers. This might consist
   *   raw values from the URL but also upcasted values, like entity objects,
   *   from route enhancers.
   */
  protected function applyRouteEnhancers($defaults, Request $request) {
    foreach ($this->enhancers as $enhancer) {
      if ($enhancer instanceof RouteEnhancerInterface && !$enhancer->applies($defaults[RouteObjectInterface::ROUTE_OBJECT])) {
        continue;
      }
      $defaults = $enhancer->enhance($defaults, $request);
    }

    return $defaults;
  }

  /**
   * Applies all route filters to a given route collection.
   *
   * This method reduces the sets of routes further down, for example by
   * checking the HTTP method.
   *
   * @param \Symfony\Component\Routing\RouteCollection $collection
   *   The route collection.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Symfony\Component\Routing\RouteCollection
   *   The filtered/sorted route collection.
   */
  protected function applyRouteFilters(RouteCollection $collection, Request $request) {
    // Route filters are expected to throw an exception themselves if they
    // end up filtering the list down to 0.
    foreach ($this->filters as $filter) {
      $collection = $filter->filter($collection, $request);
    }

    return $collection;
  }

  /**
   * Reapplies the fit order to a RouteCollection object.
   *
   * Route filters can reorder route collections. For example, routes with an
   * explicit _format requirement will be preferred. This can result in a less
   * fit route being used. For example, as a result of filtering /user/% comes
   * before /user/login. In order to not break this fundamental property of
   * routes, we need to reapply the fit order. We also need to ensure that order
   * within each group of the same fit is preserved.
   *
   * @param \Symfony\Component\Routing\RouteCollection $collection
   *   The route collection.
   *
   * @return \Symfony\Component\Routing\RouteCollection
   *   The reordered route collection.
   */
  protected function applyFitOrder(RouteCollection $collection) {
    $buckets = [];
    // Sort all the routes by fit descending.
    foreach ($collection->all() as $name => $route) {
      $fit = $route->compile()->getFit();
      $buckets += [$fit => []];
      $buckets[$fit][] = [$name, $route];
    }
    krsort($buckets);

    $flattened = array_reduce($buckets, 'array_merge', []);

    // Add them back onto a new route collection.
    $collection = new RouteCollection();
    foreach ($flattened as $pair) {
      $name = $pair[0];
      $route = $pair[1];
      $collection->add($name, $route);
    }
    return $collection;
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteCollection() {
    return new LazyRouteCollection($this->routeProvider);
  }

  /**
   * {@inheritdoc}
   */
  public function generate($name, $parameters = [], $referenceType = self::ABSOLUTE_PATH) {
    @trigger_error('Use the \Drupal\Core\Url object instead', E_USER_DEPRECATED);
    return $this->urlGenerator->generate($name, $parameters, $referenceType);
  }

}
