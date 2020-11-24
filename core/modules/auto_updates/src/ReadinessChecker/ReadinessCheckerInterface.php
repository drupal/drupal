<?php

namespace Drupal\auto_updates\ReadinessChecker;

/**
 * Defines an interface for readiness checker services.
 */
interface ReadinessCheckerInterface {

  /**
   * Gets the warnings.
   *
   * @return array
   *   An array of translatable strings if any checks fail, otherwise an empty
   *   array.
   */
  public function getWarnings(): array;

  /**
   * Gets the errors.
   *
   * @return array
   *   An array of translatable strings if any checks fail, otherwise an empty
   *   array.
   */
  public function getErrors(): array;

}
