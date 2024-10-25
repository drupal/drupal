<?php

declare(strict_types=1);

namespace Drupal\package_manager;

use Drupal\Component\Assertion\Inspector;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\system\SystemManager;
use PhpTuf\ComposerStager\API\Exception\ExceptionInterface;

/**
 * A value object to contain the results of a validation.
 *
 * @property \Drupal\Core\StringTranslation\TranslatableMarkup[] $messages
 */
final class ValidationResult {

  /**
   * Creates a ValidationResult object.
   *
   * @param int $severity
   *   The severity of the result. Should be one of the
   *   SystemManager::REQUIREMENT_* constants.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup[]|string[] $messages
   *   The result messages.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $summary
   *   A succinct summary of the result.
   * @param bool $assert_translatable
   *   Whether to assert the messages are translatable. Internal use only.
   *
   * @throws \InvalidArgumentException
   *   Thrown if $messages is empty, or if it has 2 or more items but $summary
   *   is NULL.
   */
  private function __construct(
    public readonly int $severity,
    private readonly array $messages,
    public readonly ?TranslatableMarkup $summary,
    bool $assert_translatable,
  ) {
    if ($assert_translatable) {
      assert(Inspector::assertAll(fn ($message) => $message instanceof TranslatableMarkup, $messages));
    }
    if (empty($messages)) {
      throw new \InvalidArgumentException('At least one message is required.');
    }
    if (count($messages) > 1 && !$summary) {
      throw new \InvalidArgumentException('If more than one message is provided, a summary is required.');
    }
  }

  /**
   * Implements magic ::__get() method.
   */
  public function __get(string $name): mixed {
    return match ($name) {
      // The messages must be private so that they cannot be mutated by external
      // code, but we want to allow callers to access them in the same way as
      // $this->summary and $this->severity.
      'messages' => $this->messages,
    };
  }

  /**
   * Creates an error ValidationResult object from a throwable.
   *
   * @param \Throwable $throwable
   *   The throwable.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $summary
   *   The errors summary.
   *
   * @return static
   */
  public static function createErrorFromThrowable(\Throwable $throwable, ?TranslatableMarkup $summary = NULL): static {
    // All Composer Stager exceptions are translatable.
    $is_translatable = $throwable instanceof ExceptionInterface;
    $message = $is_translatable ? $throwable->getTranslatableMessage() : $throwable->getMessage();
    return new static(SystemManager::REQUIREMENT_ERROR, [$message], $summary, $is_translatable);
  }

  /**
   * Creates an error ValidationResult object.
   *
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup[] $messages
   *   The error messages.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $summary
   *   The errors summary.
   *
   * @return static
   */
  public static function createError(array $messages, ?TranslatableMarkup $summary = NULL): static {
    return new static(SystemManager::REQUIREMENT_ERROR, $messages, $summary, TRUE);
  }

  /**
   * Creates a warning ValidationResult object.
   *
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup[] $messages
   *   The error messages.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $summary
   *   The errors summary.
   *
   * @return static
   */
  public static function createWarning(array $messages, ?TranslatableMarkup $summary = NULL): static {
    return new static(SystemManager::REQUIREMENT_WARNING, $messages, $summary, TRUE);
  }

  /**
   * Returns the overall severity for a set of validation results.
   *
   * @param \Drupal\package_manager\ValidationResult[] $results
   *   The validation results.
   *
   * @return int
   *   The overall severity of the results. Will be one of the
   *   SystemManager::REQUIREMENT_* constants.
   */
  public static function getOverallSeverity(array $results): int {
    foreach ($results as $result) {
      if ($result->severity === SystemManager::REQUIREMENT_ERROR) {
        return SystemManager::REQUIREMENT_ERROR;
      }
    }
    // If there were no errors, then any remaining results must be warnings.
    return $results ? SystemManager::REQUIREMENT_WARNING : SystemManager::REQUIREMENT_OK;
  }

  /**
   * Determines if two validation results are equivalent.
   *
   * @param self $a
   *   A validation result.
   * @param self $b
   *   Another validation result.
   *
   * @return bool
   *   TRUE if the given validation results have the same severity, summary,
   *   and messages (in the same order); otherwise FALSE.
   */
  public static function isEqual(self $a, self $b): bool {
    return (
      $a->severity === $b->severity &&
      strval($a->summary) === strval($b->summary) &&
      array_map('strval', $a->messages) === array_map('strval', $b->messages)
    );
  }

}
