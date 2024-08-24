<?php

declare(strict_types=1);

namespace Drupal\config_collection_install_test;

use Drupal\Core\Config\ConfigCollectionEvents;
use Drupal\Core\Config\ConfigCollectionInfo;
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
   * Reacts to the ConfigCollectionEvents::COLLECTION_INFO event.
   *
   * @param \Drupal\Core\Config\ConfigCollectionInfo $collection_info
   *   The configuration collection info event.
   */
  public function addCollections(ConfigCollectionInfo $collection_info) {
    $collections = $this->state->get('config_collection_install_test.collection_names', []);
    foreach ($collections as $collection) {
      $collection_info->addCollection($collection);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[ConfigCollectionEvents::COLLECTION_INFO][] = ['addCollections'];
    return $events;
  }

}
