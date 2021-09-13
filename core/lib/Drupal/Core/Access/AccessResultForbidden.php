<?php

namespace Drupal\Core\Access;

/**
 * Value object indicating a forbidden access result, with cacheability metadata.
 */
class AccessResultForbidden extends AccessResult implements AccessResultReasonInterface {

  /**
   * The reason why access is forbidden. For use in error messages.
   *
   * @var string
   */
  protected $reason;

  /**
   * Constructs a new AccessResultForbidden instance.
   *
   * @param null|string $reason
   *   (optional) A message to provide details about this access result.
   */
  public function __construct($reason = NULL) {
    $this->reason = $reason;
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
