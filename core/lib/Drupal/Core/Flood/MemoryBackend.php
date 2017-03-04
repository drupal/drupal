<?php

namespace Drupal\Core\Flood;

use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Defines the memory flood backend. This is used for testing.
 */
class MemoryBackend implements FloodInterface {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * An array holding flood events, keyed by event name and identifier.
   */
  protected $events = [];

  /**
   * Construct the MemoryBackend.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack used to retrieve the current request.
   */
  public function __construct(RequestStack $request_stack) {
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public function register($name, $window = 3600, $identifier = NULL) {
    if (!isset($identifier)) {
      $identifier = $this->requestStack->getCurrentRequest()->getClientIp();
    }
    // We can't use REQUEST_TIME here, because that would not guarantee
    // uniqueness.
    $time = microtime(TRUE);
    $this->events[$name][$identifier][$time + $window] = $time;
  }

  /**
   * {@inheritdoc}
   */
  public function clear($name, $identifier = NULL) {
    if (!isset($identifier)) {
      $identifier = $this->requestStack->getCurrentRequest()->getClientIp();
    }
    unset($this->events[$name][$identifier]);
  }

  /**
   * {@inheritdoc}
   */
  public function isAllowed($name, $threshold, $window = 3600, $identifier = NULL) {
    if (!isset($identifier)) {
      $identifier = $this->requestStack->getCurrentRequest()->getClientIp();
    }
    $limit = microtime(TRUE) - $window;
    $number = count(array_filter($this->events[$name][$identifier], function ($timestamp) use ($limit) {
      return $timestamp > $limit;
    }));
    return ($number < $threshold);
  }

  /**
   * {@inheritdoc}
   */
  public function garbageCollection() {
    foreach ($this->events as $name => $identifiers) {
      foreach ($this->events[$name] as $identifier => $timestamps) {
        // Filter by key (expiration) but preserve key => value  associations.
        $this->events[$name][$identifier] = array_filter($timestamps, function () use (&$timestamps) {
          $expiration = key($timestamps);
          next($timestamps);
          return $expiration > microtime(TRUE);
        });
      }
    }
  }

}
