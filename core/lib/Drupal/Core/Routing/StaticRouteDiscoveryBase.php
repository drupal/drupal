<?php

declare(strict_types=1);

namespace Drupal\Core\Routing;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Route;

/**
 * Provides functionality for a class subscribed to RoutingEvents::STATIC.
 *
 * @see \Drupal\Core\Routing\RoutingEvents::STATIC
 */
abstract class StaticRouteDiscoveryBase implements EventSubscriberInterface {

  /**
   * Creates a collection of routes to add to the route builder.
   *
   * @return iterable<int, \Symfony\Component\Routing\RouteCollection>
   *   The routes.
   */
  abstract protected function collectRoutes(): iterable;

  /**
   * Determines the priority of the route build event listener.
   *
   * @return int
   *   The priority of the route builder event.
   */
  abstract protected static function getPriority(): int;

  /**
   * Gets an array of default values for a route.
   *
   * @return array
   *   An array of default values for a route.
   */
  protected function resetGlobals(): array {
    return [
      'path' => NULL,
      'localized_paths' => [],
      'requirements' => [],
      'options' => [],
      'defaults' => [],
      'schemes' => [],
      'methods' => [],
      'host' => '',
      'name' => '',
      'priority' => 0,
    ];
  }

  /**
   * Creates a route.
   *
   * @param string $path
   *   The path pattern to match.
   * @param array $defaults
   *   An array of default parameter values.
   * @param array $requirements
   *   An array of requirements for parameters (regexes).
   * @param array $options
   *   An array of options.
   * @param string|null $host
   *   The host pattern to match.
   * @param array $schemes
   *   An array of URI schemes.
   * @param array $methods
   *   An array of required HTTP methods.
   * @param string|null $condition
   *   A condition that should evaluate to true for the route to match.
   *
   * @return \Symfony\Component\Routing\Route
   *   The route.
   */
  protected function createRoute(string $path, array $defaults, array $requirements, array $options, ?string $host, array $schemes, array $methods, ?string $condition): Route {
    // Ensure routes default to using Drupal's route compiler instead of
    // Symfony's.
    $options += [
      'compiler_class' => RouteCompiler::class,
    ];
    return new Route($path, $defaults, $requirements, $options, $host, $schemes, $methods, $condition);
  }

  /**
   * Adds routes to the route builder.
   *
   * @param \Drupal\Core\Routing\RouteBuildEvent $event
   *   The route build event.
   */
  public function onRouteBuild(RouteBuildEvent $event): void {
    foreach ($this->collectRoutes() as $collection) {
      $event->getRouteCollection()->addCollection($collection);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[RoutingEvents::STATIC] = ['onRouteBuild', static::getPriority()];
    return $events;
  }

}
