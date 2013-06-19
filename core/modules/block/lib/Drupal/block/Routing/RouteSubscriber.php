<?php
/**
 * @file
 * Contains \Drupal\block\Routing\RouteSubscriber.
 */

namespace Drupal\block\Routing;

use Drupal\Core\Routing\RouteBuildEvent;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber implements EventSubscriberInterface {

  /**
   * The injection plugin manager that should be passed into the route.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $pluginManager;


  /**
   * Constructs a RouteSubscriber object.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $plugin_manager
   *   The service container this object should use.
   */
  public function __construct(PluginManagerInterface $plugin_manager) {
    $this->pluginManager = $plugin_manager;
  }

  /**
   * Implements EventSubscriberInterface::getSubscribedEvents().
   */
  public static function getSubscribedEvents() {
    $events[RoutingEvents::DYNAMIC] = 'routes';
    return $events;
  }

  /**
   * Generate dynamic routes for various block pages.
   *
   * @param \Drupal\Core\Routing\RouteBuildEvent $event
   *   The route building event.
   *
   * @return \Symfony\Component\Routing\RouteCollection
   *   The route collection that contains the new dynamic route.
   */
  public function routes(RouteBuildEvent $event) {
    $collection = $event->getRouteCollection();
    foreach ($this->pluginManager->getDefinitions() as $plugin_id => $plugin) {
      list($plugin_base, $key) = explode(':', $plugin_id);
      if ($plugin_base == 'block_plugin_ui') {
        $route = new Route('admin/structure/block/list/' . $plugin_id, array(
          '_controller' => '\Drupal\block\Controller\BlockListController::listing',
          'entity_type' => 'block',
          'theme' => $key,
        ), array(
          '_block_themes_access' => 'TRUE',
        ));
        $collection->add('block_admin_display.' . $plugin_id, $route);
      }
    }
  }
}
