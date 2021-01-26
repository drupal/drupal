<?php

namespace Drupal\auto_updates\ReadinessChecker;

/**
 * Defines an interface for readiness checker services.
 */
interface ReadinessCheckerInterface {

  /**
   * Gets the result of the checker.
   *
   * @return \Drupal\auto_updates\ReadinessChecker\ReadinessCheckerResult
   *   The checker result object.
   */
  public function getResult():ReadinessCheckerResult;

}
