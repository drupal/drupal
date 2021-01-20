<?php

namespace Drupal\auto_updates\ReadinessChecker;

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines an interface for readiness checker services.
 */
interface ReadinessCheckerInterface {

  /**
   * Gets the errors summary message.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|null
   *   The errors summary if there are any messages, otherwise null
   *
   * @todo Should this return a null if there is no summary just 1 single
   *   message?
   */
  public function getErrorsSummary():?TranslatableMarkup;

  /**
   * Gets the warnings summary message.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|null
   *   The warnings summary if there are any messages, otherwise null
   *
   * @todo Should this return a null if there is no summary just 1 single
   *   message?
   */
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
