<?php

/**
 * @file
 * Contains \Drupal\menu_test\EventSubscriber\ActiveTrailSubscriber.
 */

namespace Drupal\menu_test\EventSubscriber;

use Drupal\Core\KeyValueStore\KeyValueStoreInterface;
use Drupal\Core\KeyValueStore\StateInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Tracks the active trail.
 */
class ActiveTrailSubscriber implements EventSubscriberInterface {

  /**
   * The active trail before redirect.
   *
   * @var array
   */
  protected $trail = array();

  /**
   * The state service.
   *
   * @var \Drupal\Core\KeyValueStore\StateInterface
   */
  protected $state;

  /**
   * Constructs a new ActiveTrailSubscriber.
   *
   * @param \Drupal\Core\KeyValueStore\StateInterface $state
   *   The state service.
   */
  public function __construct(StateInterface $state) {
    $this->state = $state;
  }

  /**
   * Tracks the active trail.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The event to process.
   */
  public function onKernelRequest(GetResponseEvent $event) {
    // When requested by one of the MenuTrailTestCase tests, record the initial
    // active trail during Drupal's bootstrap (before the user is redirected to
    // a custom 403 or 404 page).
    if (!$this->trail && $this->state->get('menu_test.record_active_trail') ?: FALSE) {
      $this->trail = menu_get_active_trail();
      $this->state->set('menu_test.active_trail_initial', $this->trail);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('onKernelRequest');
    return $events;
  }

}
