<?php

namespace Drupal\config_translation\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\config_translation\ConfigMapperManagerInterface;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * The mapper plugin discovery service.
   *
   * @var \Drupal\config_translation\ConfigMapperManagerInterface
   */
  protected $mapperManager;

  /**
   * Constructs a new RouteSubscriber.
   *
   * @param \Drupal\config_translation\ConfigMapperManagerInterface $mapper_manager
   *   The mapper plugin discovery service.
   */
  public function __construct(ConfigMapperManagerInterface $mapper_manager) {
    $this->mapperManager = $mapper_manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    $mappers = $this->mapperManager->getMappers($collection);

    foreach ($mappers as $mapper) {
      $collection->add($mapper->getOverviewRouteName(), $mapper->getOverviewRoute());
      $collection->add($mapper->getAddRouteName(), $mapper->getAddRoute());
      $collection->add($mapper->getEditRouteName(), $mapper->getEditRoute());
      $collection->add($mapper->getDeleteRouteName(), $mapper->getDeleteRoute());
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Come after field_ui.
    $events[RoutingEvents::ALTER] = ['onAlterRoutes', -110];
    return $events;
  }

}
