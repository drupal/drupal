<?php

namespace Drupal\Core\Access;

/**
 * Value object for a forbidden access result, with cacheability metadata.
 */
class AccessResultForbidden extends AccessResult implements AccessResultReasonInterface {

  /**
   * Constructs a new AccessResultForbidden instance.
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
  public function isForbidden() {
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
