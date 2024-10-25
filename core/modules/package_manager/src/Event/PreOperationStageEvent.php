<?php

declare(strict_types=1);

namespace Drupal\package_manager\Event;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\package_manager\ValidationResult;
use Drupal\system\SystemManager;

/**
 * Base class for events dispatched before a stage life cycle operation.
 */
abstract class PreOperationStageEvent extends StageEvent {

  /**
   * The validation results.
   *
   * @var \Drupal\package_manager\ValidationResult[]
   */
  protected $results = [];

  /**
   * Gets the validation results.
   *
   * @param int|null $severity
   *   (optional) The severity for the results to return. Should be one of the
   *   SystemManager::REQUIREMENT_* constants.
   *
   * @return \Drupal\package_manager\ValidationResult[]
   *   The validation results.
   */
  public function getResults(?int $severity = NULL): array {
    if ($severity !== NULL) {
      return array_filter($this->results, function ($result) use ($severity) {
        return $result->severity === $severity;
      });
    }
    return $this->results;
  }

  /**
   * Convenience method to flag a validation error.
   *
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup[] $messages
   *   The error messages.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $summary
   *   The summary of error messages. Must be passed if there is more than one
   *   message.
   */
  public function addError(array $messages, ?TranslatableMarkup $summary = NULL): void {
    $this->addResult(ValidationResult::createError(array_values($messages), $summary));
  }

  /**
   * Convenience method, adds an error validation result from a throwable.
   *
   * @param \Throwable $throwable
   *   The throwable.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $summary
   *   (optional) The summary of error messages.
   */
  public function addErrorFromThrowable(\Throwable $throwable, ?TranslatableMarkup $summary = NULL): void {
    $this->addResult(ValidationResult::createErrorFromThrowable($throwable, $summary));
  }

  /**
   * Adds a validation result to the event.
   *
   * @param \Drupal\package_manager\ValidationResult $result
   *   The validation result to add.
   *
   * @throws \InvalidArgumentException
   *   Thrown if the validation result is not an error.
   */
  public function addResult(ValidationResult $result): void {
    // Only errors are allowed for this event.
    if ($result->severity !== SystemManager::REQUIREMENT_ERROR) {
      throw new \InvalidArgumentException('Only errors are allowed.');
    }
    $this->results[] = $result;
  }

  /**
   * {@inheritdoc}
   */
  public function stopPropagation(): void {
    if (empty($this->getResults(SystemManager::REQUIREMENT_ERROR))) {
      $this->addErrorFromThrowable(new \LogicException('Event propagation stopped without any errors added to the event. This bypasses the package_manager validation system.'));
    }
    parent::stopPropagation();
  }

}
