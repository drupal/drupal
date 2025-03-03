<?php

declare(strict_types=1);

namespace Drupal\jsonapi_test_meta_events\EventSubscriber;

use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\jsonapi\Events\CollectRelationshipMetaEvent;
use Drupal\jsonapi\Events\CollectResourceObjectMetaEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber which tests adding metadata to ResourceObjects and relationships.
 *
 * @internal
 */
class MetaEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      CollectResourceObjectMetaEvent::class => 'addResourceObjectMeta',
      CollectRelationshipMetaEvent::class => 'addRelationshipMeta',
    ];
  }

  /**
   * @param \Drupal\jsonapi\Events\CollectResourceObjectMetaEvent $event
   *   Event to be processed.
   */
  public function addResourceObjectMeta(CollectResourceObjectMetaEvent $event): void {
    $config = \Drupal::state()->get('jsonapi_test_meta_events.object_meta', [
      'enabled_type' => FALSE,
      'enabled_id' => FALSE,
      'fields' => FALSE,
      'user_is_admin_context' => FALSE,
    ]);

    // Only continue if the recourse type is enabled.
    if ($config['enabled_type'] === FALSE || $config['enabled_type'] !== $event->getResourceObject()->getTypeName()) {
      return;
    }

    // Only apply on the referenced ID of the resource.
    if ($config['enabled_id'] !== FALSE && $config['enabled_id'] !== $event->getResourceObject()->getId()) {
      return;
    }

    if ($config['fields'] === FALSE) {
      return;
    }

    if ($config['user_is_admin_context']) {
      $event->addCacheContexts(['user.roles']);
      $event->setMetaValue('resource_meta_user_has_admin_role', $this->currentUserHasAdminRole());
      $event->setMetaValue('resource_meta_user_id', \Drupal::currentUser()->id());
    }

    // Add the metadata for each field. The field configuration must be an array
    // of field values keyed by the field name.
    foreach ($config['fields'] as $field_name) {
      $event->setMetaValue('resource_meta_' . $field_name, $event->getResourceObject()->getField($field_name)->value);
    }

    $event->addCacheTags(['jsonapi_test_meta_events.object_meta']);
  }

  /**
   * @param \Drupal\jsonapi\Events\CollectRelationshipMetaEvent $event
   *   Event to be processed.
   */
  public function addRelationshipMeta(CollectRelationshipMetaEvent $event): void {
    $config = \Drupal::state()->get('jsonapi_test_meta_events.relationship_meta', [
      'enabled_type' => FALSE,
      'enabled_id' => FALSE,
      'enabled_relation' => FALSE,
      'fields' => FALSE,
      'user_is_admin_context' => FALSE,
    ]);

    // Only continue if the resource type is enabled.
    if ($config['enabled_type'] === FALSE || $config['enabled_type'] !== $event->getResourceObject()->getTypeName()) {
      return;
    }

    // Only apply on the referenced ID of the resource.
    if ($config['enabled_id'] !== FALSE && $config['enabled_id'] !== $event->getResourceObject()->getId()) {
      return;
    }

    // Only continue if this is the correct relation.
    if ($config['enabled_relation'] === FALSE || $config['enabled_relation'] !== $event->getRelationshipFieldName()) {
      return;
    }

    $relationshipFieldName = $event->getRelationshipFieldName();

    $field = $event->getResourceObject()->getField($relationshipFieldName);
    $referencedEntities = [];
    if ($field instanceof EntityReferenceFieldItemListInterface) {
      $referencedEntities = $field->referencedEntities();
      $event->addCacheTags(['jsonapi_test_meta_events.relationship_meta']);
    }

    if ($config['user_is_admin_context'] ?? FALSE) {
      $event->addCacheContexts(['user.roles']);
      $event->setMetaValue('resource_meta_user_has_admin_role', $this->currentUserHasAdminRole());
    }

    // If no fields are specified just add a list of UUIDs to the relations.
    if ($config['fields'] === FALSE) {
      $referencedEntityIds = [];
      foreach ($referencedEntities as $entity) {
        $referencedEntityIds[] = $entity->uuid();
      }

      $event->setMetaValue('relationship_meta_' . $event->getRelationshipFieldName(), $referencedEntityIds);
      return;
    }

    // Add the metadata for each field. The field configuration must be an array
    // of field values keyed by the field name.
    foreach ($config['fields'] as $field_name) {
      $fieldValues = [];
      foreach ($referencedEntities as $entity) {
        $fieldValues[] = $entity->get($field_name)->value;
      }
      $event->setMetaValue('relationship_meta_' . $field_name, $fieldValues);
    }

  }

  /**
   * @return string
   *   The value 'yes' if the current user has an admin role, 'no' otherwise.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function currentUserHasAdminRole(): string {
    $admin_roles = \Drupal::entityTypeManager()
      ->getStorage('user_role')
      ->loadByProperties(['is_admin' => TRUE]);

    $has_admin_role = 'yes';
    if (count(array_intersect(\Drupal::currentUser()->getRoles(), array_keys($admin_roles))) === 0) {
      $has_admin_role = 'no';
    }
    return $has_admin_role;
  }

}
