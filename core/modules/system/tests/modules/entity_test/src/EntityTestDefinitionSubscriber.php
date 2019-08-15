<?php

namespace Drupal\entity_test;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface;
use Drupal\Core\Entity\EntityTypeEvents;
use Drupal\Core\Entity\EntityTypeEventSubscriberTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeListenerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldStorageDefinitionEvents;
use Drupal\Core\Field\FieldStorageDefinitionEventSubscriberTrait;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionListenerInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Test entity type and field storage definition event subscriber.
 */
class EntityTestDefinitionSubscriber implements EventSubscriberInterface, EntityTypeListenerInterface, FieldStorageDefinitionListenerInterface {

  use EntityTypeEventSubscriberTrait;
  use FieldStorageDefinitionEventSubscriberTrait;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The last installed schema repository.
   *
   * @var \Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface
   */
  protected $entityLastInstalledSchemaRepository;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Flag determining whether events should be tracked.
   *
   * @var bool
   */
  protected $trackEvents = FALSE;

  /**
   * Determines whether the live definitions should be updated.
   *
   * @var bool
   */
  protected $updateLiveDefinitions = FALSE;

  /**
   * {@inheritdoc}
   */
  public function __construct(StateInterface $state, EntityLastInstalledSchemaRepositoryInterface $entity_last_installed_schema_repository, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager) {
    $this->state = $state;
    $this->entityLastInstalledSchemaRepository = $entity_last_installed_schema_repository;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return static::getEntityTypeEvents() + static::getFieldStorageDefinitionEvents();
  }

  /**
   * {@inheritdoc}
   */
  public function onEntityTypeCreate(EntityTypeInterface $entity_type) {
    if ($this->entityLastInstalledSchemaRepository->getLastInstalledDefinition($entity_type->id())) {
      $this->storeDefinitionUpdate(EntityTypeEvents::CREATE);
    }
    $this->storeEvent(EntityTypeEvents::CREATE);

    // Retrieve the live entity type definition in order to warm the static
    // cache and then insert the new entity type definition, so we can test that
    // the cache doesn't get stale after the event has fired.
    if ($this->updateLiveDefinitions) {
      $this->entityTypeManager->getDefinition($entity_type->id());
      $this->state->set('entity_test_rev.entity_type', $entity_type);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onEntityTypeUpdate(EntityTypeInterface $entity_type, EntityTypeInterface $original) {
    $last_installed_definition = $this->entityLastInstalledSchemaRepository->getLastInstalledDefinition($entity_type->id());
    if ((string) $last_installed_definition->getLabel() === 'Updated entity test rev') {
      $this->storeDefinitionUpdate(EntityTypeEvents::UPDATE);
    }

    $this->storeEvent(EntityTypeEvents::UPDATE);

    // Retrieve the live entity type definition in order to warm the static
    // cache and then insert the new entity type definition, so we can test that
    // the cache doesn't get stale after the event has fired.
    if ($this->updateLiveDefinitions) {
      $this->entityTypeManager->getDefinition($entity_type->id());
      $this->state->set('entity_test_rev.entity_type', $entity_type);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onFieldableEntityTypeUpdate(EntityTypeInterface $entity_type, EntityTypeInterface $original, array $field_storage_definitions, array $original_field_storage_definitions, array &$sandbox = NULL) {
    $this->storeEvent(EntityTypeEvents::UPDATE);
  }

  /**
   * {@inheritdoc}
   */
  public function onEntityTypeDelete(EntityTypeInterface $entity_type) {
    if (!$this->entityLastInstalledSchemaRepository->getLastInstalledDefinition($entity_type->id())) {
      $this->storeDefinitionUpdate(EntityTypeEvents::DELETE);
    }
    $this->storeEvent(EntityTypeEvents::DELETE);

    // Retrieve the live entity type definition in order to warm the static
    // cache and then delete the new entity type definition, so we can test that
    // the cache doesn't get stale after the event has fired.
    if ($this->updateLiveDefinitions) {
      $this->entityTypeManager->getDefinition($entity_type->id());
      $this->state->set('entity_test_rev.entity_type', '');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onFieldStorageDefinitionCreate(FieldStorageDefinitionInterface $storage_definition) {
    if (isset($this->entityLastInstalledSchemaRepository->getLastInstalledFieldStorageDefinitions($storage_definition->getTargetEntityTypeId())[$storage_definition->getName()])) {
      $this->storeDefinitionUpdate(FieldStorageDefinitionEvents::CREATE);
    }
    $this->storeEvent(FieldStorageDefinitionEvents::CREATE);

    // Retrieve the live field storage definitions in order to warm the static
    // cache and then insert the new storage definition, so we can test that the
    // cache doesn't get stale after the event has fired.
    if ($this->updateLiveDefinitions) {
      $this->entityFieldManager->getFieldStorageDefinitions($storage_definition->getTargetEntityTypeId());
      $this->state->set('entity_test_rev.additional_base_field_definitions', [$storage_definition->getName() => $storage_definition]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onFieldStorageDefinitionUpdate(FieldStorageDefinitionInterface $storage_definition, FieldStorageDefinitionInterface $original) {
    $last_installed_definition = $this->entityLastInstalledSchemaRepository->getLastInstalledFieldStorageDefinitions($storage_definition->getTargetEntityTypeId())[$storage_definition->getName()];
    if ((string) $last_installed_definition->getLabel() === 'Updated field storage test') {
      $this->storeDefinitionUpdate(FieldStorageDefinitionEvents::UPDATE);
    }
    $this->storeEvent(FieldStorageDefinitionEvents::UPDATE);

    // Retrieve the live field storage definitions in order to warm the static
    // cache and then insert the new storage definition, so we can test that the
    // cache doesn't get stale after the event has fired.
    if ($this->updateLiveDefinitions) {
      $this->entityFieldManager->getFieldStorageDefinitions($storage_definition->getTargetEntityTypeId());
      $this->state->set('entity_test_rev.additional_base_field_definitions', [$storage_definition->getName() => $storage_definition]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onFieldStorageDefinitionDelete(FieldStorageDefinitionInterface $storage_definition) {
    if (!isset($this->entityLastInstalledSchemaRepository->getLastInstalledFieldStorageDefinitions($storage_definition->getTargetEntityTypeId())[$storage_definition->getName()])) {
      $this->storeDefinitionUpdate(FieldStorageDefinitionEvents::DELETE);
    }
    $this->storeEvent(FieldStorageDefinitionEvents::DELETE);

    // Retrieve the live field storage definitions in order to warm the static
    // cache and then remove the new storage definition, so we can test that the
    // cache doesn't get stale after the event has fired.
    if ($this->updateLiveDefinitions) {
      $this->entityFieldManager->getFieldStorageDefinitions($storage_definition->getTargetEntityTypeId());
      $this->state->set('entity_test_rev.additional_base_field_definitions', []);
    }
  }

  /**
   * Enables event tracking.
   */
  public function enableEventTracking() {
    $this->trackEvents = TRUE;
  }

  /**
   * Enables live definition updates.
   */
  public function enableLiveDefinitionUpdates() {
    $this->updateLiveDefinitions = TRUE;
  }

  /**
   * Checks whether an event has been dispatched.
   *
   * @param string $event_name
   *   The event name.
   *
   * @return bool
   *   TRUE if the event has been dispatched, FALSE otherwise.
   */
  public function hasEventFired($event_name) {
    return (bool) $this->state->get($event_name);
  }

  /**
   * Stores the specified event.
   *
   * @param string $event_name
   *   The event name.
   */
  protected function storeEvent($event_name) {
    if ($this->trackEvents) {
      $this->state->set($event_name, TRUE);
    }
  }

  /**
   * Checks whether the installed definitions were updated before the event.
   *
   * @param string $event_name
   *   The event name.
   *
   * @return bool
   *   TRUE if the last installed entity type of field storage definitions have
   *   been updated before the event was fired, FALSE otherwise.
   */
  public function hasDefinitionBeenUpdated($event_name) {
    return (bool) $this->state->get($event_name . '_updated_definition');
  }

  /**
   * Stores the installed definition state for the specified event.
   *
   * @param string $event_name
   *   The event name.
   */
  protected function storeDefinitionUpdate($event_name) {
    if ($this->trackEvents) {
      $this->state->set($event_name . '_updated_definition', TRUE);
    }
  }

}
