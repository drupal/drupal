<?php

namespace Drupal\auto_updates\ReadinessChecker;

/**
 * Defines an interface for readiness checker services.
 */
interface ReadinessCheckerInterface {

  /**
   * Gets the results of the checker.
   *
   * @return \Drupal\auto_updates\ReadinessChecker\ReadinessCheckerResult[]
   *   The checker results.
   */
  public function getResults(): array;

}
