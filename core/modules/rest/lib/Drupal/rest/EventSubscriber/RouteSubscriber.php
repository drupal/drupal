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

    $resources = $this->config->get('rest')->load()->get('resources');
    if ($resources && $enabled = array_intersect_key($this->manager->getDefinitions(), $resources)) {
      foreach ($enabled as $key => $resource) {
        $plugin = $this->manager->getInstance(array('id' => $key));

        // @todo Switch to ->addCollection() once http://drupal.org/node/1819018 is resolved.
        foreach ($plugin->routes() as $name => $route) {
          $collection->add("rest.$name", $route);
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

