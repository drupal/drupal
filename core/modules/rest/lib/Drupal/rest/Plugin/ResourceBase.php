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
abstract class ResourceBase extends PluginBase implements ResourceInterface {

  /**
   * Implements ResourceInterface::permissions().
   *
   * Every plugin operation method gets its own user permission. Example:
   * "restful delete entity:node" with the title "Access DELETE on Node
   * resource".
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
   * Implements ResourceInterface::routes().
   */
  public function routes() {
    $collection = new RouteCollection();
    $path_prefix = strtr($this->plugin_id, ':', '/');
    $route_name = strtr($this->plugin_id, ':', '.');

    $methods = $this->requestMethods();
    foreach ($methods as $method) {
      $lower_method = strtolower($method);
      // Only expose routes where the HTTP request method exists on the plugin.
      if (method_exists($this, $lower_method)) {
        $route = new Route("/$path_prefix/{id}", array(
          '_controller' => 'Drupal\rest\RequestHandler::handle',
          // Pass the resource plugin ID along as default property.
          '_plugin' => $this->plugin_id,
        ), array(
          // The HTTP method is a requirement for this route.
          '_method' => $method,
          '_permission' => "restful $lower_method $this->plugin_id",
        ));

        switch ($method) {
          case 'POST':
            // POST routes do not require an ID in the URL path.
            $route->setPattern("/$path_prefix");
            $route->addDefaults(array('id' => NULL));
            break;

          case 'GET':
          case 'HEAD':
            // Restrict GET and HEAD requests to the media type specified in the
            // HTTP Accept headers.
            // @todo Replace hard coded format here with available formats.
            $route->addRequirements(array('_format' => 'drupal_jsonld'));
            break;
        }

        $collection->add("$route_name.$method", $route);
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
