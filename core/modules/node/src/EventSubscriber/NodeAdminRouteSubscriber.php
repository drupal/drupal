<?php

namespace Drupal\node\EventSubscriber;

use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\RouteBuilderInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Sets the _admin_route for specific node-related routes.
 */
class NodeAdminRouteSubscriber extends RouteSubscriberBase {

  /**
   * Constructs a new NodeAdminRouteSubscriber.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\Routing\RouteBuilderInterface $routerBuilder
   *   The router builder service.
   */
  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected RouteBuilderInterface $routerBuilder,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($this->configFactory->get('node.settings')->get('use_admin_theme')) {
      foreach ($collection->all() as $route) {
        if ($route->hasOption('_node_operation_route')) {
          $route->setOption('_admin_route', TRUE);
        }
      }
    }
  }

  /**
   * Rebuilds the router when node.settings:use_admin_theme is changed.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   The event object.
   */
  public function onConfigSave(ConfigCrudEvent $event) {
    if ($event->getConfig()->getName() === 'node.settings' && $event->isChanged('use_admin_theme')) {
      $this->routerBuilder->setRebuildNeeded();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events = parent::getSubscribedEvents();
    $events[ConfigEvents::SAVE][] = ['onConfigSave', 0];
    return $events;
  }

}
