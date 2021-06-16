<?php

namespace Drupal\KernelTests;

use Drupal\Core\Routing\PreloadableRouteProviderInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Rebuilds the router when the provider is instantiated.
 */
class RouteProvider implements PreloadableRouteProviderInterface {

  use \Drupal\Core\DependencyInjection\DependencySerializationTrait;

  /**
   * Loads the real route provider from the container and rebuilds the router.
   *
   * @return \Drupal\Core\Routing\PreloadableRouteProviderInterface|\Symfony\Component\EventDispatcher\EventSubscriberInterface
   *   The route provider.
   */
  protected function lazyLoadItself() {
    if (!isset($this->service)) {
      $container = \Drupal::getContainer();
      $this->service = $container->get('simpletest.router.route_provider');
      $container->get('router.builder')->rebuild();
    }

    return $this->service;
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteCollectionForRequest(Request $request) {
    return $this->lazyLoadItself()->getRouteCollectionForRequest($request);
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteByName($name) {
    return $this->lazyLoadItself()->getRouteByName($name);
  }

  /**
   * {@inheritdoc}
   */
  public function preLoadRoutes($names) {
    return $this->lazyLoadItself()->preLoadRoutes($names);
  }

  /**
   * {@inheritdoc}
   */
  public function getRoutesByNames($names) {
    return $this->lazyLoadItself()->getRoutesByNames($names);
  }

  /**
   * {@inheritdoc}
   */
  public function getCandidateOutlines(array $parts) {
    return $this->lazyLoadItself()->getCandidateOutlines($parts);
  }

  /**
   * {@inheritdoc}
   */
  public function getRoutesByPattern($pattern) {
    return $this->lazyLoadItself()->getRoutesByPattern($pattern);
  }

  /**
   * {@inheritdoc}
   */
  public function routeProviderRouteCompare(array $a, array $b) {
    return $this->lazyLoadItself()->routeProviderRouteCompare($a, $b);
  }

  /**
   * {@inheritdoc}
   */
  public function getAllRoutes() {
    return $this->lazyLoadItself()->getAllRoutes();
  }

  /**
   * {@inheritdoc}
   */
  public function reset() {
    return $this->lazyLoadItself()->reset();
  }

}
