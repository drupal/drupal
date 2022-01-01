<?php

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
   * When ORing two access results, the result is:
   * - isForbidden() in either ⇒ isForbidden()
   * - otherwise if isAllowed() in either ⇒ isAllowed()
   * - otherwise both must be isNeutral() ⇒ isNeutral()
   *
   * Truth table:
   * @code
   *   |A N F
   * --+-----
   * A |A A F
   * N |A N F
   * F |F F F
   * @endcode
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
   * When AND is performed on two access results, the result is:
   * - isForbidden() in either ⇒ isForbidden()
   * - otherwise, if isAllowed() in both ⇒ isAllowed()
   * - otherwise, one of them is isNeutral()  ⇒ isNeutral()
   *
   * Truth table:
   * @code
   *   |A N F
   * --+-----
   * A |A N F
   * N |N N F
   * F |F F F
   * @endcode
   *
   * @param \Drupal\Core\Access\AccessResultInterface $other
   *   The other access result to AND this one with.
   *
   * @return static
   */
  public function andIf(AccessResultInterface $other);

}
