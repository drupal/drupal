<?php

namespace Drupal\Core\Routing;

use Symfony\Cmf\Component\Routing\NestedMatcher\RouteFilterInterface as BaseRouteFilterInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouteCollection;

/**
 * A route filter which lazily loads route filters, depending on the route.
 *
 * We lazy initialize route filters, because otherwise all dependencies of all
 * route filters are initialized on every request, which is slow. However, with
 * the use of lazy loading, dependencies are instantiated only when used.
 */
class LazyRouteFilter implements BaseRouteFilterInterface, ContainerAwareInterface {

  use ContainerAwareTrait;

  /**
   * Array of route filter service IDs.
   *
   * @var array
   */
  protected $serviceIds = [];

  /**
   * The initialized route filters.
   *
   * @var \Drupal\Core\Routing\RouteFilterInterface[]
   */
  protected $filters = NULL;

  /**
   * Constructs the LazyRouteEnhancer object.
   *
   * @param $service_ids
   *   Array of route filter service IDs.
   */
  public function __construct($service_ids) {
    $this->serviceIds = $service_ids;
  }

  /**
   * For each route, filter down the route collection.
   *
   * @param \Symfony\Component\Routing\RouteCollection $route_collection
   *   A collection of routes to apply filter checks to.
   */
  public function setFilters(RouteCollection $route_collection) {
    /** @var \Symfony\Component\Routing\Route $route **/
    foreach ($route_collection as $route) {
      $service_ids = [];
      foreach ($this->getFilters() as $service_id => $filter) {
        if ($filter instanceof RouteFilterInterface && $filter->applies($route)) {
          $service_ids[] = $service_id;
        }
      }
      if ($service_ids) {
        $route->setOption('_route_filters', array_unique($service_ids));
      }
    }
  }

  /**
   * For each route, gets a list of applicable enhancers to the route.
   *
   * @return \Symfony\Cmf\Component\Routing\Enhancer\RouteEnhancerInterface[]|\Drupal\Core\Routing\Enhancer\RouteEnhancerInterface[]
   */
  protected function getFilters() {
    if (!isset($this->filters)) {
      foreach ($this->serviceIds as $service_id) {
        $this->filters[$service_id] = $this->container->get($service_id);
      }
    }
    return $this->filters;
  }

  /**
   * {@inheritdoc}
   */
  public function filter(RouteCollection $collection, Request $request) {
    $filter_ids = [];
    foreach ($collection->all() as $route) {
      $filter_ids = array_merge($filter_ids, $route->getOption('_route_filters') ?: []);
    }
    $filter_ids = array_unique($filter_ids);

    if (isset($filter_ids)) {
      foreach ($filter_ids as $filter_id) {
        if ($filter = $this->container->get($filter_id, ContainerInterface::NULL_ON_INVALID_REFERENCE)) {
          $collection = $filter->filter($collection, $request);
        }
      }
    }
    return $collection;
  }

}
