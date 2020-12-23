<?php

namespace Drupal\auto_updates\ReadinessChecker;

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines an interface for readiness checker services.
 */
interface ReadinessCheckerInterface {

  public function getErrorsSummary():?TranslatableMarkup;

  public function getWarningsSummary():?TranslatableMarkup;

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
