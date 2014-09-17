<?php

/**
 * @file
 * Contains \Drupal\Core\Access\AccessResultInterface.
 */

namespace Drupal\Core\Access;

/**
 * Interface for access result value objects.
 *
 * IMPORTANT NOTE: You have to call isAllowed() when you want to know whether
 * someone has access. Just using
 * @code
 * if ($access_result) {
 *   // The user has access!
 * }
 * else {
 *   // The user doesn't have access!
 * }
 * @endcode
 * would never enter the else-statement and hence introduce a critical security
 * issue.
 *
 * Note: you can check whether access is neither explicitly allowed nor
 * explicitly forbidden:
 *
 * @code
 * $no_opinion = !$access->isAllowed() && !$access->isForbidden();
 * @endcode
 */
interface AccessResultInterface {

  /**
   * Checks whether this access result indicates access is explicitly allowed.
   *
   * @return bool
   */
  public function isAllowed();

  /**
   * Checks whether this access result indicates access is explicitly forbidden.
   *
   * @return bool
   */
  public function isForbidden();

  /**
   * Combine this access result with another using OR.
   *
   * When OR-ing two access results, the result is:
   * - isForbidden() in either ⇒ isForbidden()
   * - isAllowed() in either ⇒ isAllowed()
   * - neither isForbidden() nor isAllowed() => !isAllowed() && !isForbidden()
   *
   * @param \Drupal\Core\Access\AccessResultInterface $other
   *   The other access result to OR this one with.
   *
   * @return $this
   */
  public function orIf(AccessResultInterface $other);

  /**
   * Combine this access result with another using AND.
   *
   * When OR-ing two access results, the result is:
   * - isForbidden() in either ⇒ isForbidden()
   * - isAllowed() in both ⇒ isAllowed()
   * - neither isForbidden() nor isAllowed() => !isAllowed() && !isForbidden()
   *
   * @param \Drupal\Core\Access\AccessResultInterface $other
   *   The other access result to AND this one with.
   *
   * @return $this
   */
  public function andIf(AccessResultInterface $other);

}
