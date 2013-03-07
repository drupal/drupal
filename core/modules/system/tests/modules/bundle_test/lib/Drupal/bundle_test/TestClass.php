<?php

/**
 * @file
 * Definition of Drupal\bundle_test\TestClass.
 */

namespace Drupal\bundle_test;

use Drupal\Core\KeyValueStore\KeyValueStoreInterface;
use Drupal\Core\DestructableInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class TestClass implements EventSubscriberInterface, DestructableInterface {

  /**
   * The state keyvalue collection.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected $state;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\KeyValueStore\KeyValueStoreInterface $state
   *   The state key value store.
   */
  public function __construct(KeyValueStoreInterface $state) {
    $this->state = $state;
  }

  /**
   * A simple kernel listener method.
   */
  public function onKernelRequestTest(GetResponseEvent $event) {
    drupal_set_message(t('The bundle_test event subscriber fired!'));
  }

  /**
   * Registers methods as kernel listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('onKernelRequestTest', 100);
    return $events;
  }

  /**
   * Implements \Drupal\Core\DestructableInterface::destruct().
   */
  public function destruct() {
    $this->state->set('bundle_test.destructed', TRUE);
  }
}
