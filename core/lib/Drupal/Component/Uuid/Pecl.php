<?php

namespace Drupal\Component\Uuid;

/**
 * Generates a UUID using the PECL extension.
 */
class Pecl implements UuidInterface {

  /**
   * {@inheritdoc}
   */
  public function generate() {
    return uuid_create(UUID_TYPE_DEFAULT);
  }

}
