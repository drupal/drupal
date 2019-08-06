<?php

namespace Drupal\entity_test;

use Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface;
use Drupal\Core\Entity\EntityTypeEvents;
use Drupal\Core\Entity\EntityTypeEventSubscriberTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeListenerInterface;
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
   * Flag determining whether events should be tracked.
   *
   * @var bool
   */
  protected $trackEvents = FALSE;

  /**
   * {@inheritdoc}
   */
  public function __construct(StateInterface $state, EntityLastInstalledSchemaRepositoryInterface $entity_last_installed_schema_repository) {
    $this->state = $state;
    $this->entityLastInstalledSchemaRepository = $entity_last_installed_schema_repository;
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
  }

  /**
   * {@inheritdoc}
   */
  public function onFieldStorageDefinitionCreate(FieldStorageDefinitionInterface $storage_definition) {
    if (isset($this->entityLastInstalledSchemaRepository->getLastInstalledFieldStorageDefinitions($storage_definition->getTargetEntityTypeId())[$storage_definition->getName()])) {
      $this->storeDefinitionUpdate(FieldStorageDefinitionEvents::CREATE);
    }
    $this->storeEvent(FieldStorageDefinitionEvents::CREATE);
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
  }

  /**
   * {@inheritdoc}
   */
  public function onFieldStorageDefinitionDelete(FieldStorageDefinitionInterface $storage_definition) {
    if (!isset($this->entityLastInstalledSchemaRepository->getLastInstalledFieldStorageDefinitions($storage_definition->getTargetEntityTypeId())[$storage_definition->getName()])) {
      $this->storeDefinitionUpdate(FieldStorageDefinitionEvents::DELETE);
    }
    $this->storeEvent(FieldStorageDefinitionEvents::DELETE);
  }

  /**
   * Enables event tracking.
   */
  public function enableEventTracking() {
    $this->trackEvents = TRUE;
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
   *   been updated before the was fired, FALSE otherwise.
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
