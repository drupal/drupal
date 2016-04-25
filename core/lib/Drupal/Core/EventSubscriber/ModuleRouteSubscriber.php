<?php

namespace Drupal\Core\EventSubscriber;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * A route subscriber to remove routes that depend on modules being enabled.
 */
class ModuleRouteSubscriber extends RouteSubscriberBase {

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
  protected function alterRoutes(RouteCollection $collection) {
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
