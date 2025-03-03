<?php

declare(strict_types=1);

namespace Drupal\language_events_test;

use Drupal\Core\State\StateInterface;
use Drupal\language\Config\LanguageConfigOverrideEvents;
use Drupal\language\Config\LanguageConfigOverrideCrudEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber for testing Language events.
 */
class EventSubscriber implements EventSubscriberInterface {

  /**
   * Constructs the Event Subscriber object.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state key value store.
   */
  public function __construct(private StateInterface $state) {
  }

  /**
   * Reacts to config event.
   *
   * @param \Drupal\language\Config\LanguageConfigOverrideCrudEvent $event
   *   The language configuration event.
   * @param string $event_name
   *   The event name.
   */
  public function configEventRecorder(LanguageConfigOverrideCrudEvent $event, string $event_name): void {
    $override = $event->getLanguageConfigOverride();
    $event_info = [
      'event_name' => $event_name,
      'current_override_data' => $override->get(),
      'original_override_data' => $override->getOriginal(),
    ];

    // Record all events that occur.
    $all_events = $this->state->get('language_events_test.all_events', []);
    $override_name = $override->getName();
    if (!isset($all_events[$event_name][$override_name])) {
      $all_events[$event_name][$override_name] = [];
    }
    $all_events[$event_name][$override_name][] = $event_info;
    $this->state->set('language_events_test.all_events', $all_events);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[LanguageConfigOverrideEvents::SAVE_OVERRIDE][] = ['configEventRecorder'];
    $events[LanguageConfigOverrideEvents::DELETE_OVERRIDE][] = ['configEventRecorder'];
    return $events;
  }

}
