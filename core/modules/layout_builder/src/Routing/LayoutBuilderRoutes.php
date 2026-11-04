<?php

namespace Drupal\layout_builder\Routing;

use Drupal\Core\Routing\RouteBuildEvent;
use Drupal\Core\Routing\RoutingEvents;
use Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Provides routes for the Layout Builder UI.
 *
 * @internal
 *   Tagged services are internal.
 */
class LayoutBuilderRoutes implements EventSubscriberInterface {

  /**
   * Constructs a new LayoutBuilderRoutes.
   *
   * @param \Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface $sectionStorageManager
   *   The section storage manager.
   */
  public function __construct(
    protected SectionStorageManagerInterface $sectionStorageManager,
  ) {
  }

  /**
   * Alters existing routes for a specific collection.
   *
   * @param \Drupal\Core\Routing\RouteBuildEvent $event
   *   The route build event.
   */
  public function onAlterRoutes(RouteBuildEvent $event) {
    $collection = $event->getRouteCollection();
    foreach ($this->sectionStorageManager->getDefinitions() as $plugin_id => $definition) {
      $this->sectionStorageManager->loadEmpty($plugin_id)->buildRoutes($collection);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    // Run after \Drupal\field_ui\Routing\RouteSubscriber.
    $events[RoutingEvents::ALTER] = ['onAlterRoutes', -110];
    return $events;
  }

}
