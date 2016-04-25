<?php

namespace Drupal\Core\Routing;

use Drupal\Core\Routing\Enhancer\RouteEnhancerInterface;
use Symfony\Cmf\Component\Routing\Enhancer\RouteEnhancerInterface as BaseRouteEnhancerInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouteCollection;

/**
 * A route enhancer which lazily loads route enhancers, depending on the route.
 *
 * We lazy initialize route enhancers, because otherwise all dependencies of
 * all route enhancers are initialized on every request, which is slow. However,
 * with the use of lazy loading, dependencies are instantiated only when used.
 */
class LazyRouteEnhancer implements BaseRouteEnhancerInterface, ContainerAwareInterface {

  use ContainerAwareTrait;

  /**
   * Array of enhancers service IDs.
   *
   * @var array
   */
  protected $serviceIds = [];

  /**
   * The initialized route enhancers.
   *
   * @var \Symfony\Cmf\Component\Routing\Enhancer\RouteEnhancerInterface[]|\Drupal\Core\Routing\Enhancer\RouteEnhancerInterface[]
   */
  protected $enhancers = NULL;

  /**
   * Constructs the LazyRouteEnhancer object.
   *
   * @param $service_ids
   *   Array of enhancers service IDs.
   */
  public function __construct($service_ids) {
    $this->serviceIds = $service_ids;
  }

  /**
   * For each route, saves a list of applicable enhancers to the route.
   *
   * @param \Symfony\Component\Routing\RouteCollection $route_collection
   *   A collection of routes to apply enhancer checks to.
   */
  public function setEnhancers(RouteCollection $route_collection) {
    /** @var \Symfony\Component\Routing\Route $route **/
    foreach ($route_collection as $route_name => $route) {
      $service_ids = [];
      foreach ($this->getEnhancers() as $service_id => $enhancer) {
        if ((!$enhancer instanceof RouteEnhancerInterface) || $enhancer->applies($route)) {
          $service_ids[] = $service_id;
        }
      }
      if ($service_ids) {
        $route->setOption('_route_enhancers', array_unique($service_ids));
      }
    }
  }

  /**
   * For each route, gets a list of applicable enhancer to the route.
   *
   * @return \Symfony\Cmf\Component\Routing\Enhancer\RouteEnhancerInterface[]|\Drupal\Core\Routing\Enhancer\RouteEnhancerInterface[]
   */
  protected function getEnhancers() {
    if (!isset($this->enhancers)) {
      foreach ($this->serviceIds as $service_id) {
        $this->enhancers[$service_id] = $this->container->get($service_id);
      }
    }
    return $this->enhancers;
  }

  /**
   * {@inheritdoc}
   */
  public function enhance(array $defaults, Request $request) {
    /** @var \Symfony\Component\Routing\Route $route */
    $route = $defaults[RouteObjectInterface::ROUTE_OBJECT];

    $enhancer_ids = $route->getOption('_route_enhancers');

    if (isset($enhancer_ids)) {
      foreach ($enhancer_ids as $enhancer_id) {
        $defaults = $this->container->get($enhancer_id)->enhance($defaults, $request);
      }
    }

    return $defaults;
  }

}
