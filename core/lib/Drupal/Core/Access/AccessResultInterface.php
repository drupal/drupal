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
 * Objects implementing this interface are using Kleene's weak three-valued
 * logic with the isAllowed() state being TRUE, the isForbidden() state being
 * the intermediate truth value and isNeutral() being FALSE. See
 * http://en.wikipedia.org/wiki/Many-valued_logic for more.
 */
interface AccessResultInterface {

  /**
   * Checks whether this access result indicates access is explicitly allowed.
   *
   * @return bool
   *   When TRUE then isForbidden() and isNeutral() are FALSE.
   */
  public function isAllowed();

  /**
   * Checks whether this access result indicates access is explicitly forbidden.
   *
   * This is a kill switch — both orIf() and andIf() will result in
   * isForbidden() if either results are isForbidden().
   *
   * @return bool
   *   When TRUE then isAllowed() and isNeutral() are FALSE.
   */
  public function isForbidden();

  /**
   * Checks whether this access result indicates access is not yet determined.
   *
   * @return bool
   *   When TRUE then isAllowed() and isForbidden() are FALSE.
   */
  public function isNeutral();

  /**
   * Combine this access result with another using OR.
   *
   * When OR-ing two access results, the result is:
   * - isForbidden() in either ⇒ isForbidden()
   * - otherwise if isAllowed() in either ⇒ isAllowed()
   * - otherwise both must be isNeutral() ⇒ isNeutral()
   *
   * @param \Drupal\Core\Access\AccessResultInterface $other
   *   The other access result to OR this one with.
   *
   * @return static
   */
  public function orIf(AccessResultInterface $other);

  /**
   * Combine this access result with another using AND.
   *
   * When AND-ing two access results, the result is:
   * - isForbidden() in either ⇒ isForbidden()
   * - otherwise, if isAllowed() in both ⇒ isAllowed()
   * - otherwise, one of them is isNeutral()  ⇒ isNeutral()
   *
   * @param \Drupal\Core\Access\AccessResultInterface $other
   *   The other access result to AND this one with.
   *
   * @return static
   */
  public function andIf(AccessResultInterface $other);

}
