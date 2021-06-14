<?php

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
