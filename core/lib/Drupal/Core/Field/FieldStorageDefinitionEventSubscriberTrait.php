<?php

namespace Drupal\Core\Field;

/**
 * Helper methods for FieldStorageDefinitionListenerInterface.
 *
 * This allows a class implementing FieldStorageDefinitionListenerInterface to
 * subscribe and react to field storage definition events.
 *
 * @see \Symfony\Component\EventDispatcher\EventSubscriberInterface
 * @see \Drupal\Core\Field\FieldStorageDefinitionListenerInterface
 */
trait FieldStorageDefinitionEventSubscriberTrait {

  /**
   * Returns the subscribed events.
   *
   * @return array
   *   An array of subscribed event names.
   *
   * @see \Symfony\Component\EventDispatcher\EventSubscriberInterface::getSubscribedEvents()
   */
  public static function getFieldStorageDefinitionEvents() {
    $event = array('onFieldStorageDefinitionEvent', 100);
    $events[FieldStorageDefinitionEvents::CREATE][] = $event;
    $events[FieldStorageDefinitionEvents::UPDATE][] = $event;
    $events[FieldStorageDefinitionEvents::DELETE][] = $event;
    return $events;
  }

  /**
   * Listener method for any field storage definition event.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionEvent $event
   *   The field storage definition event object.
   * @param string $event_name
   *   The event name.
   */
  public function onFieldStorageDefinitionEvent(FieldStorageDefinitionEvent $event, $event_name) {
    switch ($event_name) {
      case FieldStorageDefinitionEvents::CREATE:
        $this->onFieldStorageDefinitionCreate($event->getFieldStorageDefinition());
        break;

      case FieldStorageDefinitionEvents::UPDATE:
        $this->onFieldStorageDefinitionUpdate($event->getFieldStorageDefinition(), $event->getOriginal());
        break;

      case FieldStorageDefinitionEvents::DELETE:
        $this->onFieldStorageDefinitionDelete($event->getFieldStorageDefinition());
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onFieldStorageDefinitionCreate(FieldStorageDefinitionInterface $storage_definition) {
  }

  /**
   * {@inheritdoc}
   */
  public function onFieldStorageDefinitionUpdate(FieldStorageDefinitionInterface $storage_definition, FieldStorageDefinitionInterface $original) {
  }

  /**
   * {@inheritdoc}
   */
  public function onFieldStorageDefinitionDelete(FieldStorageDefinitionInterface $storage_definition) {
  }

}
