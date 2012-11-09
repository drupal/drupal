<?php

/**
 * @file
 * Definition of Drupal\rest\Plugin\ResourceBase.
 */

namespace Drupal\rest\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Common base class for resource plugins.
 */
abstract class ResourceBase extends PluginBase {

  /**
   * Provides an array of permissions suitable for hook_permission().
   *
   * Every plugin operation method gets its own user permission. Example:
   * "restful delete entity:node" with the title "Access DELETE on Node
   * resource".
   *
   * @reutrn array
   *   The permission array.
   */
  public function permissions() {
    $permissions = array();
    $definition = $this->getDefinition();
    foreach ($this->requestMethods() as $method) {
      $lowered_method = strtolower($method);
      // Only expose permissions where the HTTP request method exists on the
      // plugin.
      if (method_exists($this, $lowered_method)) {
        $permissions["restful $lowered_method $this->plugin_id"] = array(
          'title' => t('Access @method on %label resource', array('@method' => $method, '%label' => $definition['label'])),
        );
      }
    }
    return $permissions;
  }

  /**
   * Returns a collection of routes with URL path information for the resource.
   *
   * This method determines where a resource is reachable, what path
   * replacements are used, the required HTTP method for the operation etc.
   *
   * @return \Symfony\Component\Routing\RouteCollection
   *   A collection of routes that should be registered for this resource.
   */
  public function routes() {
    $collection = new RouteCollection();

    $methods = $this->requestMethods();
    foreach ($methods as $method) {
      // Only expose routes where the HTTP request method exists on the plugin.
      if (method_exists($this, strtolower($method))) {
        $prefix = strtr($this->plugin_id, ':', '/');
        $route = new Route("/$prefix/{id}", array(
          '_controller' => 'Drupal\rest\RequestHandler::handle',
          // @todo Once http://drupal.org/node/1793520 is committed we will have
          // route object avaialble in the controller so 'plugin' property
          // should be changed to '_plugin'.
          // @see RequestHandler::handle().
          'plugin' => $this->plugin_id,
        ), array(
          // The HTTP method is a requirement for this route.
          '_method' => $method,
        ));

        $name = strtr($this->plugin_id, ':', '.');
        $collection->add("$name.$method", $route);
      }
    }

    return $collection;
  }

  /**
   * Provides predefined HTTP request methods.
   *
   * Plugins can override this method to provide additional custom request
   * methods.
   *
   * @return array
   *   The list of allowed HTTP request method strings.
   */
  protected function requestMethods() {
    return drupal_map_assoc(array(
      'HEAD',
      'GET',
      'POST',
      'PUT',
      'DELETE',
      'TRACE',
      'OPTIONS',
      'CONNECT',
      'PATCH',
    ));
  }
}
