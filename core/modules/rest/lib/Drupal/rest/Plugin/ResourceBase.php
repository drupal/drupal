<?php

/**
 * @file
 * Definition of Drupal\rest\Plugin\ResourceBase.
 */

namespace Drupal\rest\Plugin;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Common base class for resource plugins.
 */
abstract class ResourceBase extends PluginBase implements ContainerFactoryPluginInterface, ResourceInterface {

  /**
   * The available serialization formats.
   *
   * @var array
   */
  protected $serializerFormats = array();

  /**
   * Constructs a Drupal\rest\Plugin\ResourceBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, array $serializer_formats) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->serializerFormats = $serializer_formats;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, array $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats')
    );
  }

  /**
   * Implements ResourceInterface::permissions().
   *
   * Every plugin operation method gets its own user permission. Example:
   * "restful delete entity:node" with the title "Access DELETE on Node
   * resource".
   */
  public function permissions() {
    $permissions = array();
    $definition = $this->getPluginDefinition();
    foreach ($this->availableMethods() as $method) {
      $lowered_method = strtolower($method);
      $permissions["restful $lowered_method $this->pluginId"] = array(
        'title' => t('Access @method on %label resource', array('@method' => $method, '%label' => $definition['label'])),
      );
    }
    return $permissions;
  }

  /**
   * Implements ResourceInterface::routes().
   */
  public function routes() {
    $collection = new RouteCollection();
    $path_prefix = strtr($this->pluginId, ':', '/');
    $route_name = strtr($this->pluginId, ':', '.');

    $methods = $this->availableMethods();
    foreach ($methods as $method) {
      $lower_method = strtolower($method);
      $route = new Route("/$path_prefix/{id}", array(
        '_controller' => 'Drupal\rest\RequestHandler::handle',
        // Pass the resource plugin ID along as default property.
        '_plugin' => $this->pluginId,
      ), array(
        // The HTTP method is a requirement for this route.
        '_method' => $method,
        '_permission' => "restful $lower_method $this->pluginId",
      ), array(
        '_access_mode' => 'ANY',
      ));

      switch ($method) {
        case 'POST':
          // POST routes do not require an ID in the URL path.
          $route->setPattern("/$path_prefix");
          $route->addDefaults(array('id' => NULL));
          $collection->add("$route_name.$method", $route);
          break;

        case 'GET':
        case 'HEAD':
          // Restrict GET and HEAD requests to the media type specified in the
          // HTTP Accept headers.
          foreach ($this->serializerFormats as $format_name) {
            // Expose one route per available format.
            //$format_route = new Route($route->getPath(), $route->getDefaults(), $route->getRequirements());
            $format_route = clone $route;
            $format_route->addRequirements(array('_format' => $format_name));
            $collection->add("$route_name.$method.$format_name", $format_route);
          }
          break;

        default:
          $collection->add("$route_name.$method", $route);
          break;
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
    return array(
      'HEAD',
      'GET',
      'POST',
      'PUT',
      'DELETE',
      'TRACE',
      'OPTIONS',
      'CONNECT',
      'PATCH',
    );
  }

  /**
   * Implements ResourceInterface::availableMethods().
   */
  public function availableMethods() {
    $methods = $this->requestMethods();
    $available = array();
    foreach ($methods as $method) {
      // Only expose methods where the HTTP request method exists on the plugin.
      if (method_exists($this, strtolower($method))) {
        $available[] = $method;
      }
    }
    return $available;
  }

}
