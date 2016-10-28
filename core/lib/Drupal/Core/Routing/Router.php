<?php

namespace Drupal\Core\Routing;

use Drupal\Core\Path\CurrentPathStack;
use Symfony\Cmf\Component\Routing\Enhancer\RouteEnhancerInterface as BaseRouteEnhancerInterface;
use Symfony\Cmf\Component\Routing\LazyRouteCollection;
use Symfony\Cmf\Component\Routing\NestedMatcher\RouteFilterInterface as BaseRouteFilterInterface;
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
   * @var \Symfony\Cmf\Component\Routing\Enhancer\RouteEnhancerInterface[]
   */
  protected $enhancers = [];

  /**
   * Cached sorted list of enhancers.
   *
   * @var \Symfony\Cmf\Component\Routing\Enhancer\RouteEnhancerInterface[]
   */
  protected $sortedEnhancers;

  /**
   * The list of available route filters.
   *
   * @var \Symfony\Cmf\Component\Routing\NestedMatcher\RouteFilterInterface[]
   */
  protected $filters = [];

  /**
   * Cached sorted list route filters.
   *
   * @var \Symfony\Cmf\Component\Routing\NestedMatcher\RouteFilterInterface[]
   */
  protected $sortedFilters;

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
   * Adds a route enhancer to the list of used route enhancers.
   *
   * @param \Symfony\Cmf\Component\Routing\Enhancer\RouteEnhancerInterface $route_enhancer
   *   A route enhancer.
   * @param int $priority
   *   (optional) The priority of the enhancer. Higher number enhancers will be
   *   used first.
   *
   * @return $this
   */
  public function addRouteEnhancer(BaseRouteEnhancerInterface $route_enhancer, $priority = 0) {
    $this->enhancers[$priority][] = $route_enhancer;
    return $this;
  }

  /**
   * Adds a route filter to the list of used route filters.
   *
   * @param \Symfony\Cmf\Component\Routing\NestedMatcher\RouteFilterInterface $route_filter
   *   A route filter.
   * @param int $priority
   *   (optional) The priority of the filter. Higher number filters will be used
   *   first.
   *
   * @return $this
   */
  public function addRouteFilter(BaseRouteFilterInterface $route_filter, $priority = 0) {
    $this->filters[$priority][] = $route_filter;

    return $this;
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
    $collection = $this->applyRouteFilters($collection, $request);

    if ($ret = $this->matchCollection(rawurldecode($this->currentPath->getPath($request)), $collection)) {
      return $this->applyRouteEnhancers($ret, $request);
    }

    throw 0 < count($this->allow)
      ? new MethodNotAllowedException(array_unique($this->allow))
      : new ResourceNotFoundException(sprintf('No routes found for "%s".', $this->currentPath->getPath()));
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
    foreach ($this->getRouteEnhancers() as $enhancer) {
      $defaults = $enhancer->enhance($defaults, $request);
    }

    return $defaults;
  }

  /**
   * Sorts the enhancers and flattens them.
   *
   * @return \Symfony\Cmf\Component\Routing\Enhancer\RouteEnhancerInterface[]
   *   The enhancers ordered by priority.
   */
  public function getRouteEnhancers() {
    if (!isset($this->sortedEnhancers)) {
      $this->sortedEnhancers = $this->sortRouteEnhancers();
    }

    return $this->sortedEnhancers;
  }

  /**
   * Sort enhancers by priority.
   *
   * The highest priority number is the highest priority (reverse sorting).
   *
   * @return \Symfony\Cmf\Component\Routing\Enhancer\RouteEnhancerInterface[]
   *   The sorted enhancers.
   */
  protected function sortRouteEnhancers() {
    $sortedEnhancers = [];
    krsort($this->enhancers);

    foreach ($this->enhancers as $enhancers) {
      $sortedEnhancers = array_merge($sortedEnhancers, $enhancers);
    }

    return $sortedEnhancers;
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
    foreach ($this->getRouteFilters() as $filter) {
      $collection = $filter->filter($collection, $request);
    }

    return $collection;
  }

  /**
   * Sorts the filters and flattens them.
   *
   * @return \Symfony\Cmf\Component\Routing\NestedMatcher\RouteFilterInterface[]
   *   The filters ordered by priority
   */
  public function getRouteFilters() {
    if (!isset($this->sortedFilters)) {
      $this->sortedFilters = $this->sortFilters();
    }

    return $this->sortedFilters;
  }

  /**
   * Sort filters by priority.
   *
   * The highest priority number is the highest priority (reverse sorting).
   *
   * @return \Symfony\Cmf\Component\Routing\NestedMatcher\RouteFilterInterface[]
   *   The sorted filters.
   */
  protected function sortFilters() {
    $sortedFilters = [];
    krsort($this->filters);

    foreach ($this->filters as $filters) {
      $sortedFilters = array_merge($sortedFilters, $filters);
    }

    return $sortedFilters;
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
  public function generate($name, $parameters = array(), $referenceType = self::ABSOLUTE_PATH) {
    @trigger_error('Use the \Drupal\Core\Url object instead', E_USER_DEPRECATED);
    return $this->urlGenerator->generate($name, $parameters, $referenceType);
  }

}
