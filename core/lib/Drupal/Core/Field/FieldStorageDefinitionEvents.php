<?php

/**
 * @file
 * Contains \Drupal\Core\Field\FieldStorageDefinitionEvents.
 */

namespace Drupal\Core\Field;

/**
 * Contains all events thrown while handling field storage definitions.
 */
final class FieldStorageDefinitionEvents {

  /**
   * Name of the event triggered for field storage definition creation.
   *
   * This event allows you to respond to the creation of a new field storage
   * definition. The event listener method receives a
   * \Drupal\Core\Field\FieldStorageDefinitionEvent instance.
   *
   * @Event
   *
   * @see \Drupal\Core\Field\FieldStorageDefinitionEvent
   * @see \Drupal\Core\Entity\EntityManager::onFieldStorageDefinitionCreate()
   * @see \Drupal\Core\Field\FieldStorageDefinitionEventSubscriberTrait
   *
   * @var string
   */
  const CREATE = 'field_storage.definition.create';

  /**
   * Name of the event triggered for field storage definition update.
   *
   * This event allows you to respond anytime a field storage definition is
   * updated. The event listener method receives a
   * \Drupal\Core\Field\FieldStorageDefinitionEvent instance.
   *
   * @Event
   *
   * @see \Drupal\Core\Field\FieldStorageDefinitionEvent
   * @see \Drupal\Core\Entity\EntityManager::onFieldStorageDefinitionUpdate()
   * @see \Drupal\Core\Field\FieldStorageDefinitionEventSubscriberTrait
   *
   * @var string
   */
  const UPDATE = 'field_storage.definition.update';

  /**
   * Name of the event triggered for field storage definition deletion.
   *
   * This event allows you to respond anytime a field storage definition is
   * deleted. The event listener method receives a
   * \Drupal\Core\Field\FieldStorageDefinitionEvent instance.
   *
   * @Event
   *
   * @see \Drupal\Core\Field\FieldStorageDefinitionEvent
   * @see \Drupal\Core\Entity\EntityManager::onFieldStorageDefinitionDelete()
   * @see \Drupal\Core\Field\FieldStorageDefinitionEventSubscriberTrait
   *
   * @var string
   */
  const DELETE = 'field_storage.definition.delete';

}
