<?php

/**
 * @file
 * Contains \Drupal\rest\Routing\ResourceRoutes.
 */

namespace Drupal\rest\Routing;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\rest\Plugin\Type\ResourcePluginManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for REST-style routes.
 */
class ResourceRoutes extends RouteSubscriberBase {

  /**
   * The plugin manager for REST plugins.
   *
   * @var \Drupal\rest\Plugin\Type\ResourcePluginManager
   */
  protected $manager;

  /**
   * The Drupal configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a RouteSubscriber object.
   *
   * @param \Drupal\rest\Plugin\Type\ResourcePluginManager $manager
   *   The resource plugin manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The configuration factory holding resource settings.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   */
  public function __construct(ResourcePluginManager $manager, ConfigFactoryInterface $config, LoggerInterface $logger) {
    $this->manager = $manager;
    $this->config = $config;
    $this->logger = $logger;
  }

  /**
   * Alters existing routes for a specific collection.
   *
   * @param \Symfony\Component\Routing\RouteCollection $collection
   *   The route collection for adding routes.
   * @return array
   */
  protected function alterRoutes(RouteCollection $collection) {
    $routes = array();

    // Silently ignore resources that are in the settings but are not defined on
    // the plugin manager currently. That avoids exceptions when REST module is
    // enabled before another module that provides the resource plugin specified
    // in the settings.
    // @todo Remove in https://www.drupal.org/node/2308745
    $resources = $this->config->get('rest.settings')->get('resources') ?: array();
    $enabled_resources = array_intersect_key($resources, $this->manager->getDefinitions());
    if (count($resources) != count($enabled_resources)) {
      trigger_error('rest.settings lists resources relying on the following missing plugins: ' . implode(', ', array_keys(array_diff_key($resources, $enabled_resources))));
    }

    // Iterate over all enabled resource plugins.
    foreach ($enabled_resources as $id => $enabled_methods) {
      $plugin = $this->manager->getInstance(array('id' => $id));

      foreach ($plugin->routes() as $name => $route) {
        // @todo: Are multiple methods possible here?
        $methods = $route->getMethods();
        // Only expose routes where the method is enabled in the configuration.
        if ($methods && ($method = $methods[0]) && $method && isset($enabled_methods[$method])) {
          $route->setRequirement('_access_rest_csrf', 'TRUE');

          // Check that authentication providers are defined.
          if (empty($enabled_methods[$method]['supported_auth']) || !is_array($enabled_methods[$method]['supported_auth'])) {
            $this->logger->error('At least one authentication provider must be defined for resource @id', array(':id' => $id));
            continue;
          }

          // Check that formats are defined.
          if (empty($enabled_methods[$method]['supported_formats']) || !is_array($enabled_methods[$method]['supported_formats'])) {
            $this->logger->error('At least one format must be defined for resource @id', array(':id' => $id));
            continue;
          }

          // If the route has a format requirement, then verify that the
          // resource has it.
          $format_requirement = $route->getRequirement('_format');
          if ($format_requirement && !in_array($format_requirement, $enabled_methods[$method]['supported_formats'])) {
            continue;
          }

          // The configuration seems legit at this point, so we set the
          // authentication provider and add the route.
          $route->setOption('_auth', $enabled_methods[$method]['supported_auth']);
          $routes["rest.$name"] = $route;
          $collection->add("rest.$name", $route);
        }
      }
    }
  }

}
