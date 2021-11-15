<?php

namespace Drupal\user\Event;

use Drupal\Component\EventDispatcher\Event;

/**
 * Provides a user flood event for event listeners.
 */
class UserFloodEvent extends Event {

  /**
   * Flood event name.
   *
   * @var string
   */
  protected $name;

  /**
   * Flood event threshold.
   *
   * @var int
   */
  protected $threshold;

  /**
   * Flood event window.
   *
   * @var int
   */
  protected $window;

  /**
   * Flood event identifier.
   *
   * @var string
   */
  protected $identifier;

  /**
   * Flood event uid.
   *
   * @var int
   */
  protected $uid = NULL;

  /**
   * Flood event IP.
   *
   * @var string
   */
  protected $ip = NULL;

  /**
   * Constructs a user flood event object.
   *
   * @param string $name
   *   The name of the flood event.
   * @param int $threshold
   *   The threshold for the flood event.
   * @param int $window
   *   The window for the flood event.
   * @param string $identifier
   *   The identifier of the flood event.
   */
  public function __construct($name, $threshold, $window, $identifier) {
    $this->name = $name;
    $this->threshold = $threshold;
    $this->window = $window;
    $this->identifier = $identifier;
    // The identifier could be a uid or an IP, or a composite of both.
    if (is_numeric($identifier)) {
      $this->uid = $identifier;
      return;
    }
    if (strpos($identifier, '-') !== FALSE) {
      [$uid, $ip] = explode('-', $identifier);
      $this->uid = $uid;
      $this->ip = $ip;
      return;
    }
    $this->ip = $identifier;
  }

  /**
   * Gets the name of the user flood event object.
   *
   * @return string
   *   The name of the flood event.
   */
  public function getName() {
    return $this->name;
  }

  /**
   * Gets the threshold for the user flood event object.
   *
   * @return int
   *   The threshold for the flood event.
   */
  public function getThreshold() {
    return $this->threshold;
  }

  /**
   * Gets the window for the user flood event object.
   *
   * @return int
   *   The window for the flood event.
   */
  public function getWindow() {
    return $this->window;
  }

  /**
   * Gets the identifier of the user flood event object.
   *
   * @return string
   *   The identifier of the flood event.
   */
  public function getIdentifier() {
    return $this->identifier;
  }

  /**
   * Gets the IP of the user flood event object.
   *
   * @return string
   *   The IP of the flood event.
   */
  public function getIp() {
    return $this->ip;
  }

  /**
   * Gets the uid of the user flood event object.
   *
   * @return int
   *   The uid of the flood event.
   */
  public function getUid() {
    return $this->uid;
  }

  /**
   * Is the user flood event associated with an IP?
   *
   * @return bool
   *   Whether the event has an IP.
   */
  public function hasIp() {
    return !empty($this->ip);
  }

  /**
   * Is the user flood event associated with a uid?
   *
   * @return bool
   *   Whether the event has a uid.
   */
  public function hasUid() {
    return !empty($this->uid);
  }

}
