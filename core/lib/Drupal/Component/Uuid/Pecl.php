<?php

/**
 * @file
 * Contains \Drupal\Component\Uuid\Pecl.
 */

namespace Drupal\Component\Uuid;

/**
 * UUID implementation using the PECL extension.
 */
class Pecl implements UuidInterface {

  /**
   * {@inheritdoc}
   */
  public function generate() {
    return uuid_create(UUID_TYPE_DEFAULT);
  }
}
