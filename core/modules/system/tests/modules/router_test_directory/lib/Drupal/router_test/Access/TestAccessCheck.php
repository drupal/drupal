<?php

/**
 * @file
 * Contains Drupal\router_test\Access\TestAccessCheck.
 */

namespace Drupal\router_test\Access;

use Drupal\Core\Routing\Access\AccessInterface;

/**
 * Access check for test routes.
 */
class TestAccessCheck implements AccessInterface {

  /**
   * Checks access.
   *
   * @return string
   *   A \Drupal\Core\Access\AccessInterface constant value.
   */
  public function access() {
    // No opinion, so other access checks should decide if access should be
    // allowed or not.
    return static::DENY;
  }
}
