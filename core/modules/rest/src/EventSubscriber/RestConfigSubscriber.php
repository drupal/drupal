<?php

namespace Drupal\rest\EventSubscriber;

use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Routing\RouteBuilderInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * A subscriber triggering a route rebuild when certain configuration changes.
 */
class RestConfigSubscriber implements EventSubscriberInterface {

  /**
   * The router builder.
   *
   * @var \Drupal\Core\Routing\RouteBuilderInterface
   */
  protected $routerBuilder;

  /**
   * Constructs the RestConfigSubscriber.
   *
   * @param \Drupal\Core\Routing\RouteBuilderInterface $router_builder
   *   The router builder service.
   */
  public function __construct(RouteBuilderInterface $router_builder) {
    $this->routerBuilder = $router_builder;
  }

  /**
   * Informs the router builder a rebuild is needed when necessary.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   The Event to process.
   */
  public function onSave(ConfigCrudEvent $event) {
    $saved_config = $event->getConfig();
    if ($saved_config->getName() === 'rest.settings' && $event->isChanged('bc_entity_resource_permissions')) {
      $this->routerBuilder->setRebuildNeeded();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[ConfigEvents::SAVE][] = ['onSave'];
    return $events;
  }

}
