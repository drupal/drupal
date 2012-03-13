<?php

/**
 * @file
 * Definition of Drupal\Component\Uuid\UuidInterface.
 */

namespace Drupal\Component\Uuid;

/**
 * Interface that defines a UUID backend.
 */
interface UuidInterface {

  /**
   * Generates a Universally Unique IDentifier (UUID).
   *
   * @return
   *   A 32 byte integer represented as a hex string formatted with 4 hypens.
   */
  public function generate();
}
