<?php

declare(strict_types=1);

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
  public static function getSubscribedEvents(): array {
    return [
      ResourceTypeBuildEvents::BUILD => [
        ['disableResourceType'],
        ['aliasResourceTypeFields'],
        ['disableResourceTypeFields'],
        ['renameResourceType'],
      ],
    ];
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

  /**
   * Aliases any resource type fields that have been aliased by a test.
   *
   * @param \Drupal\jsonapi\ResourceType\ResourceTypeBuildEvent $event
   *   The build event.
   */
  public function aliasResourceTypeFields(ResourceTypeBuildEvent $event) {
    $aliases = \Drupal::state()->get('jsonapi_test_resource_type_builder.resource_type_field_aliases', []);
    $resource_type_name = $event->getResourceTypeName();
    if (in_array($resource_type_name, array_keys($aliases), TRUE)) {
      foreach ($event->getFields() as $field) {
        if (isset($aliases[$resource_type_name][$field->getInternalName()])) {
          $event->setPublicFieldName($field, $aliases[$resource_type_name][$field->getInternalName()]);
        }
      }
    }
  }

  /**
   * Disables any resource type fields that have been aliased by a test.
   *
   * @param \Drupal\jsonapi\ResourceType\ResourceTypeBuildEvent $event
   *   The build event.
   */
  public function disableResourceTypeFields(ResourceTypeBuildEvent $event) {
    $aliases = \Drupal::state()->get('jsonapi_test_resource_type_builder.disabled_resource_type_fields', []);
    $resource_type_name = $event->getResourceTypeName();
    if (in_array($resource_type_name, array_keys($aliases), TRUE)) {
      foreach ($event->getFields() as $field) {
        if (isset($aliases[$resource_type_name][$field->getInternalName()]) && $aliases[$resource_type_name][$field->getInternalName()] === TRUE) {
          $event->disableField($field);
        }
      }
    }
  }

  /**
   * Renames any resource types that have been renamed by a test.
   *
   * @param \Drupal\jsonapi\ResourceType\ResourceTypeBuildEvent $event
   *   The build event.
   */
  public function renameResourceType(ResourceTypeBuildEvent $event) {
    $names = \Drupal::state()->get('jsonapi_test_resource_type_builder.renamed_resource_types', []);
    $resource_type_name = $event->getResourceTypeName();
    if (isset($names[$resource_type_name])) {
      $event->setResourceTypeName($names[$resource_type_name]);
    }
  }

}
