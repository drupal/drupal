<?php

namespace Drupal\Core\Routing;

use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * The lazy route collection.
 */
class LazyRouteCollection extends RouteCollection {

  /**
   * Constructs a LazyRouteCollection.
   */
  public function __construct(
    protected RouteProviderInterface $provider,
  ) {
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
   *   The route name.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   A Route instance or null when not found
   */
  public function get($name): ?Route {
    try {
      return $this->provider->getRouteByName($name);
    }
    catch (RouteNotFoundException) {
      return NULL;
    }
  }

}
