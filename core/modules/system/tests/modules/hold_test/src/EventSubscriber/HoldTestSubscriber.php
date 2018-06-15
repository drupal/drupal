<?php

namespace Drupal\hold_test\EventSubscriber;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Response subscriber to test hold.
 */
class HoldTestSubscriber implements EventSubscriberInterface {

  const HOLD_REQUEST = 'request';
  const HOLD_RESPONSE = 'response';

  /**
   * Request hold.
   */
  public function onRequest() {
    $this->hold(static::HOLD_REQUEST);
  }

  /**
   * Response hold.
   */
  public function onRespond() {
    $this->hold(static::HOLD_RESPONSE);
  }

  /**
   * Hold process by type.
   *
   * @param string $type
   *   Type of hold.
   */
  protected function hold($type) {
    $path = \Drupal::root() . "/sites/default/files/simpletest/hold_test_$type.txt";
    do {
      $status = (bool) file_get_contents($path);
    } while ($status && (NULL === usleep(100000)));
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['onRequest'];
    $events[KernelEvents::RESPONSE][] = ['onRespond'];
    return $events;
  }

}
