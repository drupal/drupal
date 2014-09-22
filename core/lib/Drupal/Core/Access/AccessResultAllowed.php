<?php

/**
 * @file
 * Contains \Drupal\Core\Access\AccessResultAllowed.
 */

namespace Drupal\Core\Access;

/**
 * Value object indicating an allowed access result, with cacheability metadata.
 */
class AccessResultAllowed extends AccessResult {

  /**
   * {@inheritdoc}
   */
  public function isAllowed() {
    return TRUE;
  }

}
