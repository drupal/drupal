<?php

/**
 * @file
 * Contains \Drupal\Core\Access\AccessResultForbidden.
 */

namespace Drupal\Core\Access;

/**
 * Value object indicating a forbidden access result, with cacheability metadata.
 */
class AccessResultForbidden extends AccessResult {

  /**
   * {@inheritdoc}
   */
  public function isForbidden() {
    return TRUE;
  }

}
