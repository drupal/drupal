<?php

namespace Drupal\auto_updates\ReadinessChecker;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\system\SystemManager;

/**
 * A value object to contain the results of a readiness check.
 */
class ReadinessCheckerResult {

  /**
   * The summary of errors.
   *
   * @var \Drupal\Core\StringTranslation\TranslatableMarkup
   */
  protected $summary;


  /**
   * The error messages.
   *
   * @var \Drupal\Core\StringTranslation\TranslatableMarkup[]
   */
  protected $messages;

  /**
   * The ID of the check that produces the result.
   *
   * @var string
   */
  protected $checkerId;

  /**
   * The severity of the result.
   *
   * @var int
   */
  protected $severity;

  /**
   * Creates a ReadinessCheckerResult object.
   *
   * @param \Drupal\auto_updates\ReadinessChecker\ReadinessCheckerInterface $readiness_checker
   *   The readiness checker that produced this result.
   * @param int $severity
   *   The severity of the result. Should be one of the
   *   SystemManager::REQUIREMENT_* constants.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup[] $messages
   *   The error messages.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $summary
   *   The errors summary.
   */
  private function __construct(ReadinessCheckerInterface $readiness_checker, int $severity, array $messages, ?TranslatableMarkup $summary = NULL) {
    if (count($messages) > 1 && !$summary) {
      throw new \InvalidArgumentException('If more than 1 messages is provided the summary is required.');
    }
    $this->checkerId = $readiness_checker->_serviceId;
    $this->summary = $summary;
    $this->messages = $messages;
    $this->severity = $severity;
  }

  /**
   * Creates an error ReadinessCheckerResult object.
   *
   * @param \Drupal\auto_updates\ReadinessChecker\ReadinessCheckerInterface $readiness_checker
   *   The readiness checker that produced this result.
   *   The error messages.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup[] $messages
   *   The error messages.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $summary
   *   The errors summary.
   */
  public static function createErrorResult(ReadinessCheckerInterface $readiness_checker, array $messages, ?TranslatableMarkup $summary = NULL) {
    return new static(
      $readiness_checker,
      SystemManager::REQUIREMENT_ERROR,
      $messages,
      $summary
    );
  }

  /**
   * Creates an error ReadinessCheckerResult object.
   *
   * @param \Drupal\auto_updates\ReadinessChecker\ReadinessCheckerInterface $readiness_checker
   *   The readiness checker that produced this result.
   *   The error messages.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup[] $messages
   *   The error messages.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $summary
   *   The errors summary.
   */
  public static function createWarningResult(ReadinessCheckerInterface $readiness_checker, array $messages, ?TranslatableMarkup $summary = NULL) {
    return new static(
      $readiness_checker,
      SystemManager::REQUIREMENT_WARNING,
      $messages,
      $summary
    );
  }

  /**
   * Gets the summary.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|null
   *   The summary.
   */
  public function getSummary(): ?TranslatableMarkup {
    return $this->summary;
  }

  /**
   * Gets the messages.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup[]
   *   The error messages.
   */
  public function getMessages(): array {
    return $this->messages;
  }

  /**
   * Gets the service ID of the readiness checker that produced this result.
   *
   * @return string
   *   The readiness checker ID.
   */
  public function getCheckerId(): string {
    return $this->checkerId;
  }

  /**
   * The severity of the result.
   *
   * @return int
   *   Either SystemManager::REQUIREMENT_ERROR or
   *   SystemManager::REQUIREMENT_WARNING.
   */
  public function getSeverity(): int {
    return $this->severity;
  }

}
