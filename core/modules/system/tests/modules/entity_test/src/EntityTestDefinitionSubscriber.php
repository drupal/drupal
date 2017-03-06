<?php

namespace Drupal\entity_test;

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
   * Flag determining whether events should be tracked.
   *
   * @var bool
   */
  protected $trackEvents = FALSE;

  /**
   * {@inheritdoc}
   */
  public function __construct(StateInterface $state) {
    $this->state = $state;
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
    $this->storeEvent(EntityTypeEvents::CREATE);
  }

  /**
   * {@inheritdoc}
   */
  public function onEntityTypeUpdate(EntityTypeInterface $entity_type, EntityTypeInterface $original) {
    $this->storeEvent(EntityTypeEvents::UPDATE);
  }

  /**
   * {@inheritdoc}
   */
  public function onEntityTypeDelete(EntityTypeInterface $entity_type) {
    $this->storeEvent(EntityTypeEvents::DELETE);
  }

  /**
   * {@inheritdoc}
   */
  public function onFieldStorageDefinitionCreate(FieldStorageDefinitionInterface $storage_definition) {
    $this->storeEvent(FieldStorageDefinitionEvents::CREATE);
  }

  /**
   * {@inheritdoc}
   */
  public function onFieldStorageDefinitionUpdate(FieldStorageDefinitionInterface $storage_definition, FieldStorageDefinitionInterface $original) {
    $this->storeEvent(FieldStorageDefinitionEvents::UPDATE);
  }

  /**
   * {@inheritdoc}
   */
  public function onFieldStorageDefinitionDelete(FieldStorageDefinitionInterface $storage_definition) {
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

}
