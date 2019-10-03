<?php

namespace Drupal\jsonapi_test_resource_type_building\EventSubscriber;

use Drupal\jsonapi\ResourceType\ResourceTypeBuildEvents;
use Drupal\jsonapi\ResourceType\ResourceTypeBuildEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber which tests disabling resource types.
 *
 * @internal
 */
class ResourceTypeBuildEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [ResourceTypeBuildEvents::BUILD => 'disableResourceType'];
  }

  /**
   * Disables any resource types that have been disabled by a test.
   *
   * @param \Drupal\jsonapi\ResourceType\ResourceTypeBuildEvent $event
   *   The build event.
   */
  public function disableResourceType(ResourceTypeBuildEvent $event) {
    $disabled_resource_types = \Drupal::state()->get('jsonapi_test_resource_type_builder.disabled_resource_types', []);
    if (in_array($event->getResourceTypeName(), $disabled_resource_types, TRUE)) {
      $event->disableResourceType();
    }
  }

}
