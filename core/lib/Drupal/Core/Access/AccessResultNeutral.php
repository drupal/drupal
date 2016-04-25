<?php

namespace Drupal\Core\Access;

/**
 * Value object indicating a neutral access result, with cacheability metadata.
 */
class AccessResultNeutral extends AccessResult {

  /**
   * {@inheritdoc}
   */
  public function isNeutral() {
    return TRUE;
  }

}
