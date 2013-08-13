<?php

/**
 * Definition of \Drupal\rest\EventSubscriber\RouteSubscriber.
 */

namespace Drupal\rest\EventSubscriber;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Routing\RouteBuildEvent;
use Drupal\Core\Routing\RoutingEvents;
use Drupal\rest\Plugin\Type\ResourcePluginManager;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscriber for REST-style routes.
 */
class RouteSubscriber implements EventSubscriberInterface {

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
   * Adds routes to enabled REST resources.
   *
   * @param \Drupal\Core\Routing\RouteBuildEvent $event
   *   The route building event.
   */
  public function dynamicRoutes(RouteBuildEvent $event) {

    $collection = $event->getRouteCollection();
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

  /**
   * Implements EventSubscriberInterface::getSubscribedEvents().
   */
  static function getSubscribedEvents() {
    $events[RoutingEvents::DYNAMIC] = 'dynamicRoutes';
    return $events;
  }
}

