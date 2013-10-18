<?php

/**
 * @file
 * Contains \Drupal\rest\EventSubscriber\RouteSubscriber.
 */

namespace Drupal\rest\EventSubscriber;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\rest\Plugin\Type\ResourcePluginManager;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for REST-style routes.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * The plugin manager for REST plugins.
   *
   * @var \Drupal\rest\Plugin\Type\ResourcePluginManager
   */
  protected $manager;

  /**
   * The Drupal configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $config;

  /**
   * Constructs a RouteSubscriber object.
   *
   * @param \Drupal\rest\Plugin\Type\ResourcePluginManager $manager
   *   The resource plugin manager.
   * @param \Drupal\Core\Config\ConfigFactory $config
   *   The configuration factory holding resource settings.
   */
  public function __construct(ResourcePluginManager $manager, ConfigFactory $config) {
    $this->manager = $manager;
    $this->config = $config;
  }

  /**
   * {@inheritdoc}
   */
  protected function routes(RouteCollection $collection) {
    $enabled_resources = $this->config->get('rest.settings')->load()->get('resources');

    // Iterate over all enabled resource plugins.
    foreach ($enabled_resources as $id => $enabled_methods) {
      $plugin = $this->manager->getInstance(array('id' => $id));

      foreach ($plugin->routes() as $name => $route) {
        $method = $route->getRequirement('_method');
        // Only expose routes where the method is enabled in the configuration.
        if ($method && isset($enabled_methods[$method])) {
          $route->setRequirement('_access_rest_csrf',  'TRUE');

          // If the array of configured format restrictions is empty for a
          // method always add the route.
          if (empty($enabled_methods[$method])) {
            $collection->add("rest.$name", $route);
            continue;
          }
          // Check if there are authentication provider restrictions in the
          // configuration and apply them to the route.
          if (!empty($enabled_methods[$method]['supported_auth']) && is_array($enabled_methods[$method]['supported_auth'])) {
            $route->setOption('_auth', $enabled_methods[$method]['supported_auth']);
          }
          // If there is no format requirement or if it matches the
          // configuration also add the route.
          $format_requirement = $route->getRequirement('_format');
          if (!$format_requirement || empty($enabled_methods[$method]['supported_formats']) || in_array($format_requirement, $enabled_methods[$method]['supported_formats'])) {
            $collection->add("rest.$name", $route);
          }
        }
      }
    }
  }

}
