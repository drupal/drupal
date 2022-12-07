<?php

namespace Drupal\Core\Routing;

use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class LazyRouteCollection extends RouteCollection {
  /**
   * The route provider for this generator.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected $provider;

  /**
   * Constructs a LazyRouteCollection.
   */
  public function __construct(RouteProviderInterface $provider) {
    $this->provider = $provider;
  }

  /**
   * {@inheritdoc}
   */
  public function getIterator(): \ArrayIterator {
    return new \ArrayIterator($this->all());
  }

  /**
   * Gets the number of Routes in this collection.
   *
   * @return int
   *   The number of routes
   */
  public function count(): int {
    return count($this->all());
  }

  /**
   * Returns all routes in this collection.
   *
   * @return \Symfony\Component\Routing\Route[]
   *   An array of routes
   */
  public function all(): array {
    return $this->provider->getRoutesByNames(NULL);
  }

  /**
   * Gets a route by name.
   *
   * @param string $name
   *   The route name
   *
   * @return \Symfony\Component\Routing\Route|null
   *   A Route instance or null when not found
   */
  public function get($name): ?Route {
    try {
      return $this->provider->getRouteByName($name);
    }
    catch (RouteNotFoundException $e) {
      return NULL;
    }
  }

}
