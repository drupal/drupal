<?php

/**
 * @file
 * Contains \Drupal\Core\Menu\LocalTaskDerivativeBase.
 */

namespace Drupal\Core\Menu;

use Drupal\Component\Plugin\Derivative\DerivativeBase;

/**
 * Provides a getPluginIdFromRoute method for local task derivatives.
 */
class LocalTaskDerivativeBase extends DerivativeBase {

  /**
   * Finds the local task ID of a route given the route name.
   *
   * @param string $route_name
   *   The route name.
   * @param array $local_tasks
   *   An array of all local task definitions.
   *
   * @return string|null
   *   Returns the local task ID of the given route or NULL if none is found.
   */
  protected function getPluginIdFromRoute($route_name, &$local_tasks) {
    foreach ($local_tasks as $plugin_id => $local_task) {
      if ($local_task['route_name'] == $route_name) {
        return $plugin_id;
        break;
      }
    }
  }

}
