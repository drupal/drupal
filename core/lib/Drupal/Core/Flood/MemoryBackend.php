<?php

/**
 * @file
 * Definition of Drupal\Core\Flood\MemoryBackend.
 */

namespace Drupal\Core\Flood;

use Symfony\Component\HttpFoundation\Request;

/**
 * Defines the memory flood backend. This is used for testing.
 */
class MemoryBackend implements FloodInterface {

  /**
   * A request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * An array holding flood events, keyed by event name and identifier.
   */
  protected $events = array();

  /**
   * Construct the MemoryBackend.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HttpRequest object representing the current request.
   */
  public function __construct(Request $request) {
    $this->request = $request;
  }

  /**
   * Implements Drupal\Core\Flood\FloodInterface::register().
   */
  public function register($name, $window = 3600, $identifier = NULL) {
    if (!isset($identifier)) {
      $identifier = $this->request->getClientIP();
    }
    // We can't use REQUEST_TIME here, because that would not guarantee
    // uniqueness.
    $time = microtime(true);
    $this->events[$name][$identifier][$time + $window] = $time;
  }

  /**
   * Implements Drupal\Core\Flood\FloodInterface::clear().
   */
  public function clear($name, $identifier = NULL) {
    if (!isset($identifier)) {
      $identifier = $this->request->getClientIP();
    }
    unset($this->events[$name][$identifier]);
  }

  /**
   * Implements Drupal\Core\Flood\FloodInterface::isAllowed().
   */
  public function isAllowed($name, $threshold, $window = 3600, $identifier = NULL) {
    if (!isset($identifier)) {
      $identifier = $this->request->getClientIP();
    }
    $limit = microtime(true) - $window;
    $number = count(array_filter($this->events[$name][$identifier], function ($timestamp) use ($limit) {
      return $timestamp > $limit;
    }));
    return ($number < $threshold);
  }

  /**
   * Implements Drupal\Core\Flood\FloodInterface::garbageCollection().
   */
  public function garbageCollection() {
    foreach ($this->events as $name => $identifiers) {
      foreach ($this->events[$name] as $identifier => $timestamps) {
        // Filter by key (expiration) but preserve key => value  associations.
        $this->events[$name][$identifier] = array_filter($timestamps, function () use (&$timestamps) {
          $expiration = key($timestamps);
          next($timestamps);
          return $expiration > microtime(true);
        });
      }
    }
  }
}
