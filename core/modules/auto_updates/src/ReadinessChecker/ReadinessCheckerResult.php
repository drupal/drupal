<?php

namespace Drupal\auto_updates\ReadinessChecker;

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * A value object for readiness checker result.
 */
class ReadinessCheckerResult {

  /**
   * The summary of errors.
   *
   * @var \Drupal\Core\StringTranslation\TranslatableMarkup
   */
  protected $errorsSummary;

  /**
   * The summary of warnings.
   *
   * @var \Drupal\Core\StringTranslation\TranslatableMarkup
   */
  protected $warningsSummary;

  /**
   * The error messages.
   *
   * @var \Drupal\Core\StringTranslation\TranslatableMarkup[]
   */
  protected $errorMessages;

  /**
   * The warning messages.
   *
   * @var \Drupal\Core\StringTranslation\TranslatableMarkup[]
   */
  protected $warningMessages;

  /**
   * The ID of the check that produces the result.
   *
   * @var string
   */
  protected $checkerId;

  /**
   * Creates a ReadinessCheckerResult object.
   *
   * @param \Drupal\auto_updates\ReadinessChecker\ReadinessCheckerInterface $readiness_checker
   *   The readiness checker that produced this result.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $errors_summary
   *   The errors summary.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup[] $error_messages
   *   The error messages.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $warnings_summary
   *   The warnings summary.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup[] $warning_messages
   *   The warning messages.
   */
  public function __construct(ReadinessCheckerInterface $readiness_checker, ?TranslatableMarkup $errors_summary, array $error_messages, ?TranslatableMarkup $warnings_summary = NULL, array $warning_messages = []) {
    $this->checkerId = $readiness_checker->_serviceId;
    $this->errorsSummary = $errors_summary;
    $this->warningsSummary = $warnings_summary;
    $this->errorMessages = $error_messages;
    $this->warningMessages = $warning_messages;
  }

  /**
   * Gets the error summary.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|null
   *   The summary.
   */
  public function getErrorsSummary():?TranslatableMarkup {
    return $this->errorsSummary;
  }

  /**
   * Gets the warning summary.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|null
   *   The summary.
   */
  public function getWarningsSummary():?TranslatableMarkup {
    return $this->warningsSummary;
  }

  /**
   * Gets the error messages.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup[]
   *   The error messages.
   */
  public function getErrorMessages():array {
    return $this->errorMessages;
  }

  /**
   * Gets the warning messages.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup[]
   *   The warning messages.
   */
  public function getWarningMessages():array {
    return $this->warningMessages;
  }

  /**
   * Get the readiness checker id that produces the result.
   *
   * @return string
   *   The readiness checker id.
   */
  public function getCheckerId():string {
    return $this->checkerId;
  }

}
