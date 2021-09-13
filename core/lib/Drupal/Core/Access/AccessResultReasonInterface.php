<?php

namespace Drupal\Core\Access;

/**
 * Interface for access result value objects with stored reason for developers.
 *
 * For example, a developer can specify the reason for forbidden access:
 * @code
 * new AccessResultForbidden('You are not authorized to hack core');
 * @endcode
 *
 * @see \Drupal\Core\Access\AccessResultInterface
 */
interface AccessResultReasonInterface extends AccessResultInterface {

  /**
   * Gets the reason for this access result.
   *
   * @return string
   *   The reason of this access result or an empty string if no reason is
   *   provided.
   */
  public function getReason();

  /**
   * Sets the reason for this access result.
   *
   * @param $reason string|null
   *   The reason of this access result or NULL if no reason is provided.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result instance.
   */
  public function setReason($reason);

}
