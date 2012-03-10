<?php

/**
 * @file
 * Definition of Drupal\Component\Uuid\Pecl.
 */

namespace Drupal\Component\Uuid;

/**
 * UUID implementation using the PECL extension.
 */
class Pecl implements UuidInterface {

  /**
   * Implements Drupal\Component\Uuid\UuidInterface::generate().
   */
  public function generate() {
    return uuid_create(UUID_TYPE_DEFAULT);
  }
}
