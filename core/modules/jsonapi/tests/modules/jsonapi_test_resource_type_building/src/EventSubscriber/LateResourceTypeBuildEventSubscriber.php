<?php

declare(strict_types=1);

namespace Drupal\jsonapi_test_resource_type_building\EventSubscriber;

use Drupal\jsonapi\ResourceType\ResourceTypeBuildEvents;
use Drupal\jsonapi\ResourceType\ResourceTypeBuildEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber which tests enabling disabled resource type fields.
 *
 * @internal
 */
class LateResourceTypeBuildEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      ResourceTypeBuildEvents::BUILD => [
        ['enableResourceTypeFields'],
      ],
    ];
  }

  /**
   * Disables any resource type fields that have been aliased by a test.
   *
   * @param \Drupal\jsonapi\ResourceType\ResourceTypeBuildEvent $event
   *   The build event.
   */
  public function enableResourceTypeFields(ResourceTypeBuildEvent $event): void {
    $aliases = \Drupal::state()->get('jsonapi_test_resource_type_builder.enabled_resource_type_fields', []);
    $resource_type_name = $event->getResourceTypeName();
    if (in_array($resource_type_name, array_keys($aliases), TRUE)) {
      foreach ($event->getFields() as $field) {
        if (isset($aliases[$resource_type_name][$field->getInternalName()]) && $aliases[$resource_type_name][$field->getInternalName()] === TRUE) {
          $event->enableField($field);
        }
      }
    }
  }

}
