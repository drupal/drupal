<?php

/**
 * @file
 * Contains \Drupal\Component\Uuid\Com.
 */

namespace Drupal\Component\Uuid;

/**
 * UUID implementation using the Windows internal GUID extension.
 *
 * @see http://php.net/com_create_guid
 */
class Com implements UuidInterface {
  /**
   * {@inheritdoc}
   */
  public function generate() {
    // Remove {} wrapper and make lower case to keep result consistent.
    return strtolower(trim(com_create_guid(), '{}'));
  }
}
