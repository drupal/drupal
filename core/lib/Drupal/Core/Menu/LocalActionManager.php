<?php

/**
 * @file
 * Contains \Drupal\Core\Menu\LocalActionManager.
 */

namespace Drupal\Core\Menu;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Menu\LocalActionInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;

/**
 * Manages discovery and instantiation of menu local action plugins.
 *
 * Menu local actions are links that lead to actions like "add new". The plugin
 * format allows them (if needed) to dynamically generate a title or the path
 * they link to. The annotation on the plugin provides the default title,
 * and the list of routes where the action should be rendered.
 */
class LocalActionManager extends DefaultPluginManager {

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
   * Constructs a LocalActionManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Symfony\Component\HttpKernel\Controller\ControllerResolverInterface $controller_resolver
   *   An object to use in introspecting route methods.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object to use for building titles and paths for plugin
   *   instances.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(\Traversable $namespaces, ControllerResolverInterface $controller_resolver, Request $request, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/Menu/LocalAction', $namespaces, array(), 'Drupal\Core\Annotation\Menu\LocalAction');

    $this->controllerResolver = $controller_resolver;
    $this->request = $request;
    $this->alterInfo($module_handler, 'menu_local_actions');
  }

  /**
   * Gets the title for a local action.
   *
   * @param \Drupal\Core\Menu\LocalActionInterface $local_action
   *   An object to get the title from.
   *
   * @return string
   *   The title (already localized).
   *
   * @throws \BadMethodCallException
   *   If the plugin does not implement the getTitle() method.
   */
  public function getTitle(LocalActionInterface $local_action) {
    $controller = array($local_action, 'getTitle');
    $arguments = $this->controllerResolver->getArguments($this->request, $controller);
    return call_user_func_array($controller, $arguments);
  }

  /**
   * Gets the Drupal path for a local action.
   *
   * @param \Drupal\Core\Menu\LocalActionInterface $local_action
   *   An object to get the path from.
   *
   * @return string
   *   The path.
   *
   * @throws \BadMethodCallException
   *   If the plugin does not implement the getPath() method.
   */
  public function getPath(LocalActionInterface $local_action) {
    $controller = array($local_action, 'getPath');
    $arguments = $this->controllerResolver->getArguments($this->request, $controller);
    return call_user_func_array($controller, $arguments);
  }

  /**
   * Finds all local actions that appear on a named route.
   *
   * @param string $route_name
   *   The route for which to find local actions.
   *
   * @return \Drupal\Core\Menu\LocalActionInterface[]
   *   An array of LocalActionInterface objects that appear on the route path.
   */
  public function getActionsForRoute($route_name) {
    if (!isset($this->instances[$route_name])) {
      $this->instances[$route_name] = array();
      // @todo - optimize this lookup by compiling or caching.
      foreach ($this->getDefinitions() as $plugin_id => $action_info) {
        if (in_array($route_name, $action_info['appears_on'])) {
          $plugin = $this->createInstance($plugin_id);
          $this->instances[$route_name][$plugin_id] = $plugin;
        }
      }
    }
    return $this->instances[$route_name];
  }

}
