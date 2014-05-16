<?php

/**
 * @file
 * Contains \Drupal\config_collection_install_test\EventSubscriber.
 */

namespace Drupal\config_collection_install_test;

use Drupal\Core\Config\ConfigCollectionNamesEvent;
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
   * Reacts to the ConfigEvents::COLLECTION_NAMES event.
   *
   * @param \Drupal\Core\Config\ConfigCollectionNamesEvent $event
   *   The configuration collection names event.
   */
  public function addCollectionNames(ConfigCollectionNamesEvent $event) {
    $event->addCollectionNames($this->state->get('config_collection_install_test.collection_names', array()));
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[ConfigEvents::COLLECTION_NAMES][] = array('addCollectionNames');
    return $events;
  }

}
