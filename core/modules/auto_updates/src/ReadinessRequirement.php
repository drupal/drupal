<?php

namespace Drupal\auto_updates;

use Drupal\auto_updates\ReadinessChecker\ReadinessCheckerManager;

/**
 * Class for generating the readiness checkers requirement.
 *
 * @see update_requirements()
 *
 * @internal
 *   This class implements logic output the messages from readiness checkers. It
 *   should not be called directly.
 */
final class ReadinessRequirement {

  /**
   * The readm
   * @var \Drupal\auto_updates\ReadinessChecker\ReadinessCheckerManager
   */
  protected $readinessCheckerManager;


  /**
   * ReadinessRequirement constructor.
   */
  public function __construct(ReadinessCheckerManager $readinessCheckerManager) {
    $this->readinessCheckerManager = $readinessCheckerManager;
  }
}
