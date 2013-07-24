<?php

/**
 * @file
 * Contains \Drupal\Core\Menu\MenuLocalTaskManager.
 */

namespace Drupal\Core\Menu;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Routing\RouteProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;

/**
 * Manages discovery and instantiation of menu local task plugins.
 *
 * This manager finds plugins that are rendered as local tasks (usually tabs).
 * Derivatives are supported for modules that wish to generate multiple tabs on
 * behalf of something else.
 */
class LocalTaskManager extends DefaultPluginManager {

  /**
   * A controller resolver object.
   *
   * @var \Symfony\Component\HttpKernel\Controller\ControllerResolverInterface
   */
  protected $controllerResolver;

  /**
   * A request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The plugin instances.
   *
   * @var array
   */
  protected $instances = array();

  /**
   * The route provider to load routes by name.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected $routeProvider;

  /**
   * Constructs a \Drupal\Core\Menu\LocalTaskManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations,
   * @param \Symfony\Component\HttpKernel\Controller\ControllerResolverInterface $controller_resolver
   *   An object to use in introspecting route methods.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object to use for building titles and paths for plugin instances.
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The route provider to load routes by name.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.u
   */
  public function __construct(\Traversable $namespaces, ControllerResolverInterface $controller_resolver, Request $request, RouteProviderInterface $route_provider, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/Menu/LocalTask', $namespaces, array(), 'Drupal\Core\Annotation\Menu\LocalTask');
    $this->controllerResolver = $controller_resolver;
    $this->request = $request;
    $this->routeProvider = $route_provider;
    $this->alterInfo($module_handler, 'local_tasks');
  }

  /**
   * Gets the title for a local task.
   *
   * @param \Drupal\Core\Menu\LocalTaskInterface $local_task
   *   A local task plugin instance to get the title for.
   *
   * @return string
   *   The localized title.
   */
  public function getTitle(LocalTaskInterface $local_task) {
    $controller = array($local_task, 'getTitle');
    $arguments = $this->controllerResolver->getArguments($this->request, $controller);
    return call_user_func_array($controller, $arguments);
  }

  /**
   * Gets the Drupal path for a local task.
   *
   * @param \Drupal\Core\Menu\LocalTaskInterface $local_task
   *   The local task plugin instance to get the path for.
   *
   * @return string
   *   The path.
   */
  public function getPath(LocalTaskInterface $local_task) {
    $controller = array($local_task, 'getPath');
    $arguments = $this->controllerResolver->getArguments($this->request, $controller);
    return call_user_func_array($controller, $arguments);
  }

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
  public function getLocalTasksForRoute($route_name) {
    if (!isset($this->instances[$route_name])) {
      $this->instances[$route_name] = array();
      // @todo - optimize this lookup by compiling or caching.
      $definitions = $this->getDefinitions();
      // We build the hierarchy by finding all tabs that should
      // appear on the current route.
      $tab_root_ids = array();
      $parents = array();
      foreach ($definitions as $plugin_id => $task_info) {
        if ($route_name == $task_info['route_name']) {
          $tab_root_ids[$task_info['tab_root_id']] = TRUE;
          // Tabs that link to the current route are viable parents
          // and their parent and children should be visible also.
          // @todo - this only works for 2 levels of tabs.
          // instead need to iterate up.
          $parents[$plugin_id] = TRUE;
          if (!empty($task_info['tab_parent_id'])) {
            $parents[$task_info['tab_parent_id']] = TRUE;
          }
        }
      }
      if ($tab_root_ids) {
        // Find all the plugins with the same root and that are at the top
        // level or that have a visible parent.
        $children = array();
        foreach ($definitions  as $plugin_id => $task_info) {
          if (!empty($tab_root_ids[$task_info['tab_root_id']]) && (empty($task_info['tab_parent_id']) || !empty($parents[$task_info['tab_parent_id']]))) {
            // Concat '> ' with root ID for the parent of top-level tabs.
            $parent = empty($task_info['tab_parent_id']) ? '> ' . $task_info['tab_root_id'] : $task_info['tab_parent_id'];
            $children[$parent][$plugin_id] = $task_info;
          }
        }
        foreach (array_keys($tab_root_ids) as $root_id) {
          // Convert the tree keyed by plugin IDs into a simple one with
          // integer depth.  Create instances for each plugin along the way.
          $level = 0;
          // We used this above as the top-level parent array key.
          $next_parent = '> ' . $root_id;
          do {
            $parent = $next_parent;
            $next_parent = FALSE;
            foreach ($children[$parent] as $plugin_id => $task_info) {
              $plugin = $this->createInstance($plugin_id);
              $this->instances[$route_name][$level][$plugin_id] = $plugin;
              // Normally, l() compares the href of every link with the current
              // path and sets the active class accordingly. But the parents of
              // the current local task may be on a different route in which
              // case we have to set the class manually by flagging it active.
              if (!empty($parents[$plugin_id]) && $route_name != $task_info['route_name']) {
                $plugin->setActive();
              }
              if (isset($children[$plugin_id])) {
                // This tab has visible children
                $next_parent = $plugin_id;
              }
            }
            $level++;
          } while ($next_parent);
        }
      }
    }
    return $this->instances[$route_name];
  }

  /**
   * Gets the render array for all local tasks.
   *
   * @param string $route_name
   *   The route for which to make renderable local tasks.
   *
   * @return array
   *   A render array as expected by theme_menu_local_tasks.
   */
  public function getTasksBuild($route_name) {
    $tree = $this->getLocalTasksForRoute($route_name);
    $build = array();

    // Collect all route names.
    $route_names = array();
    foreach ($tree as $instances) {
      foreach ($instances as $child) {
        $route_names[] = $child->getRouteName();
      }
    }
    // Fetches all routes involved in the tree.
    $routes = $route_names ? $this->routeProvider->getRoutesByNames($route_names) : array();

    foreach ($tree as $level => $instances) {
      foreach ($instances as $child) {
        $path = $this->getPath($child);
        // Find out whether the user has access to the task.
        $route = $routes[$child->getRouteName()];
        $map = array();
        // @todo - replace this call when we have a real service for it.
        $access = menu_item_route_access($route, $path, $map);
        if ($access) {
          // Need to flag the list element as active for a tab for the current
          // route or if the plugin is set active (i.e. the parent tab).
          $active = ($route_name == $child->getRouteName() || $child->getActive());
          // @todo It might make sense to use menu link entities instead of
          //   arrays.
          $menu_link = array(
            'title' => $this->getTitle($child),
            'href' => $path,
            'localized_options' => $child->getOptions(),
          );
          $build[$level][$path] = array(
            '#theme' => 'menu_local_task',
            '#link' => $menu_link,
            '#active' => $active,
            '#weight' => $child->getWeight(),
            '#access' => $access,
          );
        }
      }
    }
    return $build;
  }

}
