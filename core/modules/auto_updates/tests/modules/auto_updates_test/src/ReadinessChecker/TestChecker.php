<?php

namespace Drupal\auto_updates_test\ReadinessChecker;

use Drupal\auto_updates\ReadinessChecker\ReadinessCheckerInterface;
use Drupal\auto_updates\ReadinessChecker\ReadinessCheckerResult;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * A test readiness checker.
 */
class TestChecker implements ReadinessCheckerInterface {

  use StringTranslationTrait;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Creates a TestChecker object.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   */
  public function __construct(StateInterface $state) {
    $this->state = $state;
  }

  /**
   * Sets messages for the this readiness checker.
   *
   * This is a static method to enable setting the expected messages before the
   * test module is enabled.
   *
   * @param string[] $errors
   *   The error messages.
   * @param string[] $warnings
   *   The warning messages.
   * @param string|null $errors_summary
   *   The errors summary.
   * @param string|null $warnings_summary
   *   The warnings summary.
   */
  public static function setTestMessages(array $errors = [], array $warnings = [], ?string $errors_summary = NULL, ?string $warnings_summary = NULL): void {
    \Drupal::state()->set(
      'auto_updates_test.checker_results',
      [
        'errors_summary' => $errors_summary ? new TranslatableMarkup($errors_summary) : NULL,
        'errors' => $errors,
        'warnings_summary' => $warnings_summary ? new TranslatableMarkup($warnings_summary) : NULL,
        'warnings' => $warnings,
      ]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getResult(): ?ReadinessCheckerResult {
    if ($checker_results = $this->state->get('auto_updates_test.checker_results', NULL)) {
      return new ReadinessCheckerResult(
        $this,
        $checker_results['errors_summary'],
        $checker_results['errors'],
        $checker_results['warnings_summary'],
        $checker_results['warnings'],
      );
    }
    return NULL;
  }

}
