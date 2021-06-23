<?php

namespace Drupal\auto_updates\ReadinessChecker;

/**
 * Defines an interface for readiness checker services.
 */
interface ReadinessCheckerInterface {

  /**
   * Gets the result of the checker.
   *
   * @return \Drupal\auto_updates\ReadinessChecker\ReadinessCheckerResult|null
   *   The checker result object or NULL if there now warnings or errors.
   */
  public function getResult(): ?ReadinessCheckerResult;

}
