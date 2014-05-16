<?php

/**
 * @file
 * Contains \Drupal\Core\Access\AccessInterface.
 */

namespace Drupal\Core\Access;

/**
 * Provides access check results.
 */
interface AccessInterface {

  /**
   * Grant access.
   *
   * A checker should return this value to indicate that it grants access.
   */
  const ALLOW = 'ALLOW';

  /**
   * Deny access.
   *
   * A checker should return this value to indicate it does not grant access.
   */
  const DENY = 'DENY';

  /**
   * Block access.
   *
   * A checker should return this value to indicate that it wants to completely
   * block access, regardless of any other access checkers. Most checkers
   * should prefer AccessInterface::DENY.
   */
  const KILL = 'KILL';

}
