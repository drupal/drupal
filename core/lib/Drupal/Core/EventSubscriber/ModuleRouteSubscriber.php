<?php

/**
 * @file
 * Contains \Drupal\Core\EventSubscriber\ModuleRouteSubscriber.
 */

namespace Drupal\Core\EventSubscriber;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\RouteBuildEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Routing\RoutingEvents;

/**
 * A route subscriber to remove routes that depend on modules being enabled.
 */
class ModuleRouteSubscriber implements EventSubscriberInterface {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a ModuleRouteSubscriber object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[RoutingEvents::ALTER] = 'removeRoutes';
    return $events;
  }

  /**
   * Removes any routes that have an unmet module dependency.
   *
   * @param \Drupal\Core\Routing\RouteBuildEvent $event
   *   The route building event.
   */
  public function removeRoutes(RouteBuildEvent $event) {
    $collection = $event->getRouteCollection();

    foreach ($collection as $name => $route) {
      if ($route->hasRequirement('_module_dependencies')) {
        $modules = $route->getRequirement('_module_dependencies');

        $explode_and = $this->explodeString($modules, '+');
        if (count($explode_and) > 1) {
          foreach ($explode_and as $module) {
            // If any moduleExists() call returns FALSE, remove the route and
            // move on to the next.
            if (!$this->moduleHandler->moduleExists($module)) {
              $collection->remove($name);
              continue 2;
            }
          }
        }
        else {
          // OR condition, exploding on ',' character.
          foreach ($this->explodeString($modules, ',') as $module) {
            if ($this->moduleHandler->moduleExists($module)) {
              continue 2;
            }
          }
          // If no modules are found, and we get this far, remove the route.
          $collection->remove($name);
        }
      }
    }
  }

  /**
   * Explodes a string based on a separator.
   *
   * @param string $string
   *   The string to explode.
   * @param string $separator
   *   The string separator to explode with.
   *
   * @return array
   *   An array of exploded (and trimmed) values.
   */
  protected function explodeString($string, $separator = ',') {
    return array_filter(array_map('trim', explode($separator, $string)));
  }

}
