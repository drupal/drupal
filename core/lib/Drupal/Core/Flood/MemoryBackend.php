<?php

namespace Drupal\Core\Flood;

use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Defines the memory flood backend. This is used for testing.
 */
class MemoryBackend implements FloodInterface, PrefixFloodInterface {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * An array holding flood events, keyed by event name and identifier.
   *
   * @var array
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
    $this->events[$name][$identifier][] = ['expire' => $time + $window, 'time' => $time];
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
  public function clearByPrefix(string $name, string $prefix): void {
    foreach ($this->events as $event_name => $identifier) {
      $identifier_key = key($identifier);
      $identifier_parts = explode("-", $identifier_key);
      $identifier_prefix = reset($identifier_parts);
      if ($prefix == $identifier_prefix && $name == $event_name) {
        unset($this->events[$event_name][$identifier_key]);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isAllowed($name, $threshold, $window = 3600, $identifier = NULL) {
    if (!isset($identifier)) {
      $identifier = $this->requestStack->getCurrentRequest()->getClientIp();
    }
    if (!isset($this->events[$name][$identifier])) {
      return $threshold > 0;
    }
    $limit = microtime(TRUE) - $window;
    $number = count(array_filter($this->events[$name][$identifier], function ($entry) use ($limit) {
      return $entry['time'] > $limit;
    }));
    return ($number < $threshold);
  }

  /**
   * {@inheritdoc}
   */
  public function garbageCollection() {
    foreach ($this->events as $name => $identifiers) {
      foreach ($this->events[$name] as $identifier => $entries) {
        // Remove expired entries.
        $this->events[$name][$identifier] = array_filter($entries, function ($entry) {
          return $entry['expire'] > microtime(TRUE);
        });
      }
    }
  }

}
