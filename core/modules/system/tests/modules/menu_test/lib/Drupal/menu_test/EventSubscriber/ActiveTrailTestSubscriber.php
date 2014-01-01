<?php

/**
 * @file
 * Contains \Drupal\menu_test\EventSubscriber\ActiveTrailTestSubscriber.
 */

namespace Drupal\menu_test\EventSubscriber;

use Drupal\Core\KeyValueStore\KeyValueStoreInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Tracks the active trail.
 */
class ActiveTrailTestSubscriber implements EventSubscriberInterface {

  /**
   * The state keyvalue store.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected $state;

  /**
   * Constructs the ActiveTrailTestSubscriber.
   *
   * @param \Drupal\Core\KeyValueStore\KeyValueStoreInterface $state
   *   The state keyvalue store.
   */
  public function __construct(KeyValueStoreInterface $state) {
    $this->state = $state;
  }

  /**
   * Records the active trail.
   */
  public function onKernelException(KernelEvent $event) {
    // When requested by one of the MenuTrailTestCase tests, record the initial
    // active trail during Drupal's bootstrap (before the user is redirected to a
    // custom 403 or 404 page). See menu_test_custom_403_404_callback().
    if ($this->state->get('menu_test.record_active_trail') ?: FALSE) {
      $this->state->set('menu_test.active_trail_initial', menu_get_active_trail());
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // The actual exception subscriber has a weight of -128 so this comes first.
    $events[KernelEvents::EXCEPTION] = 'onKernelException';
    return $events;
  }

}
