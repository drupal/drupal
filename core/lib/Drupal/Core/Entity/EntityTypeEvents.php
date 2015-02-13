<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\EntityTypeEvents.
 */

namespace Drupal\Core\Entity;

/**
 * Contains all events thrown while handling entity types.
 */
final class EntityTypeEvents {

  /**
   * The name of the event triggered when a new entity type is created.
   *
   * This event allows modules to react to a new entity type being created. The
   * event listener method receives a \Drupal\Core\Entity\EntityTypeEvent
   * instance.
   *
   * @Event
   *
   * @see \Drupal\Core\Entity\EntityTypeEvent
   * @see \Drupal\Core\Entity\EntityManager::onEntityTypeCreate()
   * @see \Drupal\Core\Entity\EntityTypeEventSubscriberTrait
   * @see \Drupal\views\EventSubscriber\ViewsEntitySchemaSubscriber::onEntityTypeCreate()
   *
   * @var string
   */
  const CREATE = 'entity_type.definition.create';

  /**
   * The name of the event triggered when an existing entity type is updated.
   *
   * This event allows modules to react whenever an existing entity type is
   * updated. The event listener method receives a
   * \Drupal\Core\Entity\EntityTypeEvent instance.
   *
   * @Event
   *
   * @see \Drupal\Core\Entity\EntityTypeEvent
   * @see \Drupal\Core\Entity\EntityManager::onEntityTypeUpdate()
   * @see \Drupal\Core\Entity\EntityTypeEventSubscriberTrait
   * @see \Drupal\views\EventSubscriber\ViewsEntitySchemaSubscriber::onEntityTypeUpdate()
   *
   * @var string
   */
  const UPDATE = 'entity_type.definition.update';

  /**
   * The name of the event triggered when an existing entity type is deleted.
   *
   * This event allows modules to react whenever an existing entity type is
   * deleted.  The event listener method receives a
   * \Drupal\Core\Entity\EntityTypeEvent instance.
   *
   * @Event
   *
   * @see \Drupal\Core\Entity\EntityTypeEvent
   * @see \Drupal\Core\Entity\EntityManager::onEntityTypeDelete()
   * @see \Drupal\Core\Entity\EntityTypeEventSubscriberTrait
   * @see \Drupal\views\EventSubscriber\ViewsEntitySchemaSubscriber::onEntityTypeDelete()
   *
   * @var string
   */
  const DELETE = 'entity_type.definition.delete';

}
