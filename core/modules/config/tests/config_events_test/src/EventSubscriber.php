<?php

/**
 * @file
 * Contains \Drupal\config_events_test\EventSubscriber.
 */

namespace Drupal\config_events_test;


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
   * @param string $name
   *   The event name.
   */
  public function configEventRecorder(ConfigCrudEvent $event, $name) {
    $config = $event->getConfig();
    $this->state->set('config_events_test.event', array(
      'event_name' => $name,
      'current_config_data' => $config->get(),
      'original_config_data' => $config->getOriginal(),
      'raw_config_data' => $config->getRawData()
    ));
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[ConfigEvents::SAVE][] = array('configEventRecorder');
    $events[ConfigEvents::DELETE][] = array('configEventRecorder');
    $events[ConfigEvents::RENAME][] = array('configEventRecorder');
    return $events;
  }
}
