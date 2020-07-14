<?php

namespace Drupal\user;

use Drupal\user\Event\UserEvents;
use Drupal\user\Event\UserFloodEvent;
use Drupal\Core\Flood\FloodInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * User Flood Control service.
 *
 * @see: \Drupal\Core\Flood\DatabaseBackend
 */
class UserFloodControl implements UserFloodControlInterface {

  /**
   * The decorated flood service.
   *
   * @var \Drupal\Core\Flood\FloodInterface
   */
  protected $flood;

  /**
   * Event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Construct the UserFloodControl.
   *
   * @param \Drupal\Core\Flood\FloodInterface $flood
   *   The flood service.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack used to retrieve the current request.
   */
  public function __construct(FloodInterface $flood, EventDispatcherInterface $event_dispatcher, RequestStack $request_stack) {
    $this->flood = $flood;
    $this->eventDispatcher = $event_dispatcher;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public function isAllowed($name, $threshold, $window = 3600, $identifier = NULL) {
    if ($this->flood->isAllowed($name, $threshold, $window, $identifier)) {
      return TRUE;
    }
    // Register flood control blocked login event.
    $event_map['user.failed_login_ip'] = UserEvents::FLOOD_BLOCKED_IP;
    $event_map['user.failed_login_user'] = UserEvents::FLOOD_BLOCKED_USER;
    $event_map['user.http_login'] = UserEvents::FLOOD_BLOCKED_USER;

    if (isset($event_map[$name])) {
      if (empty($identifier)) {
        $identifier = $this->requestStack->getCurrentRequest()->getClientIp();
      }
      $event = new UserFloodEvent($name, $threshold, $window, $identifier);
      $this->eventDispatcher->dispatch($event, $event_map[$name]);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function register($name, $window = 3600, $identifier = NULL) {
    return $this->flood->register($name, $window, $identifier);
  }

  /**
   * {@inheritdoc}
   */
  public function clear($name, $identifier = NULL) {
    return $this->flood->clear($name, $identifier);
  }

  /**
   * {@inheritdoc}
   */
  public function garbageCollection() {
    return $this->flood->garbageCollection();
  }

}
