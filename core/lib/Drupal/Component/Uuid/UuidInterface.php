<?php

/**
 * @file
 * Contains \Drupal\Component\Uuid\UuidInterface.
 */

namespace Drupal\Component\Uuid;

/**
 * Interface for generating UUIDs.
 */
interface UuidInterface {

  /**
   * Generates a Universally Unique IDentifier (UUID).
   *
   * @return
   *   A 16 byte integer represented as a hex string formatted with 4 hyphens.
   */
  public function generate();
}
