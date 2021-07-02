<?php

namespace Drupal\Core\Menu;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Cache\RefinableCacheableDependencyInterface;

/**
 * Manages discovery and instantiation of menu local task plugins.
 *
 * This manager finds plugins that are rendered as local tasks (usually tabs).
 * Derivatives are supported for modules that wish to generate multiple tabs on
 * behalf of something else.
 */
interface LocalTaskManagerInterface extends PluginManagerInterface {

  /**
   * Gets the title for a local task.
   *
   * @param \Drupal\Core\Menu\LocalTaskInterface $local_task
   *   A local task plugin instance to get the title for.
   *
   * @return string
   *   The localized title.
   */
  public function getTitle(LocalTaskInterface $local_task);

  /**
   * Find all local tasks that appear on a named route.
   *
   * @param string $route_name
   *   The route for which to find local tasks.
   *
   * @return array
   *   Returns an array of task levels. Each task level contains instances
   *   of local tasks (LocalTaskInterface) which appear on the tab route.
   *   The array keys are the depths and the values are arrays of plugin
   *   instances.
   */
  public function getLocalTasksForRoute($route_name);

  /**
   * Gets the render array for all local tasks.
   *
   * @param string $current_route_name
   *   The route for which to make renderable local tasks.
   * @param \Drupal\Core\Cache\RefinableCacheableDependencyInterface $cacheability
   *   The cacheability metadata for the local tasks.
   *
   * @return array
   *   A render array as expected by menu-local-tasks.html.twig.
   */
  public function getTasksBuild($current_route_name, RefinableCacheableDependencyInterface &$cacheability);

  /**
   * Renders the local tasks (tabs) for the given route.
   *
   * @param string $route_name
   *   The route for which to make renderable local tasks.
   * @param int $level
   *   The level of tasks you ask for. Primary tasks are 0, secondary are 1.
   *
   * @return array
   *   An array containing
   *   - tabs: Local tasks render array for the requested level.
   *   - route_name: The route name for the current page used to collect the
   *     local tasks.
   *
   * @see hook_menu_local_tasks_alter()
   */
  public function getLocalTasks($route_name, $level = 0);

}
