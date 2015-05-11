<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\EntityTypeEventSubscriberTrait.
 */

namespace Drupal\Core\Entity;

/**
 * Helper methods for EntityTypeListenerInterface.
 *
 * This allows a class implementing EntityTypeListenerInterface to subscribe and
 * react to entity type events.
 *
 * @see \Symfony\Component\EventDispatcher\EventSubscriberInterface
 * @see \Drupal\Core\Entity\EntityTypeListenerInterface
 */
trait EntityTypeEventSubscriberTrait {

  /**
   * Gets the subscribed events.
   *
   * @return array
   *   An array of subscribed event names.
   *
   * @see \Symfony\Component\EventDispatcher\EventSubscriberInterface::getSubscribedEvents()
   */
  public static function getEntityTypeEvents() {
    $event = array('onEntityTypeEvent', 100);
    $events[EntityTypeEvents::CREATE][] = $event;
    $events[EntityTypeEvents::UPDATE][] = $event;
    $events[EntityTypeEvents::DELETE][] = $event;
    return $events;
  }

  /**
   * Listener method for any entity type definition event.
   *
   * @param \Drupal\Core\Entity\EntityTypeEvent $event
   *   The field storage definition event object.
   * @param string $event_name
   *   The event name.
   */
  public function onEntityTypeEvent(EntityTypeEvent $event, $event_name) {
    switch ($event_name) {
      case EntityTypeEvents::CREATE:
        $this->onEntityTypeCreate($event->getEntityType());
        break;

      case EntityTypeEvents::UPDATE:
        $this->onEntityTypeUpdate($event->getEntityType(), $event->getOriginal());
        break;

      case EntityTypeEvents::DELETE:
        $this->onEntityTypeDelete($event->getEntityType());
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onEntityTypeCreate(EntityTypeInterface $entity_type) {
  }

  /**
   * {@inheritdoc}
   */
  public function onEntityTypeUpdate(EntityTypeInterface $entity_type, EntityTypeInterface $original) {
  }

  /**
   * {@inheritdoc}
   */
  public function onEntityTypeDelete(EntityTypeInterface $entity_type) {
  }

}
