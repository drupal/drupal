<?php

namespace Drupal\Core\Access;

/**
 * Value object indicating a neutral access result, with cacheability metadata.
 */
class AccessResultNeutral extends AccessResult implements AccessResultReasonInterface {

  /**
   * Constructs a new AccessResultNeutral instance.
   *
   * @param null|string $reason
   *   (optional) A message to provide details about this access result.
   */
  public function __construct(
    protected $reason = NULL,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function isNeutral() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getReason() {
    return (string) $this->reason;
  }

  /**
   * {@inheritdoc}
   */
  public function setReason($reason) {
    $this->reason = $reason;
    return $this;
  }

}
