<?php

/**
 * @file
 * Contains \Drupal\Core\PrivateKey.
 */

namespace Drupal\Core;

use Drupal\Core\KeyValueStore\StateInterface;
use Drupal\Component\Utility\Crypt;

/**
 * Manages the Drupal private key.
 */
class PrivateKey {

  /**
   * The state service.
   *
   * @var \Drupal\Core\KeyValueStore\StateInterface
   */
  protected $state;

  /**
   * Constructs the token generator.
   *
   * @param \Drupal\Core\KeyValueStore\StateInterface $state
   *   The state service.
   */
  function __construct(StateInterface $state) {
    $this->state = $state;
  }

  /**
   * Gets the private key.
   *
   * @return string
   *   The private key.
   */
  public function get() {
    if (!$key = $this->state->get('system.private_key')) {
      $key = $this->create();
      $this->set($key);
    }

    return $key;
  }

  /**
   * Sets the private key.
   *
   * @param string $key
   *  The private key to set.
   */
  public function set($key) {
    return $this->state->set('system.private_key', $key);
  }

  /**
   * Creates a new private key.
   *
   * @return string
   *   The private key.
   */
  protected function create() {
    return Crypt::randomBytesBase64(55);
  }

}
