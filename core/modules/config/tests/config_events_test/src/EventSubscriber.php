<?php

declare(strict_types=1);

namespace Drupal\config_events_test;

use Drupal\Core\Config\ConfigCollectionEvents;
use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\State\StateInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class EventSubscriber implements EventSubscriberInterface {

  /**
   * The state key value store.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs the Event Subscriber object.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state key value store.
   */
  public function __construct(StateInterface $state) {
    $this->state = $state;
  }

  /**
   * Reacts to config event.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   The configuration event.
   * @param string $event_name
   *   The event name.
   */
  public function configEventRecorder(ConfigCrudEvent $event, $event_name) {
    $config = $event->getConfig();
    $event_info = [
      'event_name' => $event_name,
      'current_config_data' => $config->get(),
      'original_config_data' => $config->getOriginal(),
      'raw_config_data' => $config->getRawData(),
    ];
    $this->state->set('config_events_test.event', $event_info);

    // Record all events that occur.
    $all_events = $this->state->get('config_events_test.all_events', []);
    $config_name = $config->getName();
    $all_events[$event_name][$config_name][] = $event_info;
    $this->state->set('config_events_test.all_events', $all_events);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[ConfigEvents::SAVE][] = ['configEventRecorder'];
    $events[ConfigEvents::DELETE][] = ['configEventRecorder'];
    $events[ConfigEvents::RENAME][] = ['configEventRecorder'];
    $events[ConfigCollectionEvents::SAVE_IN_COLLECTION][] = ['configEventRecorder'];
    $events[ConfigCollectionEvents::DELETE_IN_COLLECTION][] = ['configEventRecorder'];
    $events[ConfigCollectionEvents::RENAME_IN_COLLECTION][] = ['configEventRecorder'];
    return $events;
  }

}
