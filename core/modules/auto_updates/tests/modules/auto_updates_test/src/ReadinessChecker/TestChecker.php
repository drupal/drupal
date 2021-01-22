<?php

namespace Drupal\auto_updates_test\ReadinessChecker;

use Drupal\auto_updates\ReadinessChecker\ReadinessCheckerInterface;
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
   * Gets the test messages set in state.
   *
   * @return mixed[]
   *   The test messages.
   *
   * @see \Drupal\Tests\auto_updates\Kernel\ReadinessChecker\TestCheckerTrait::setTestMessages()
   */
  protected function getMessages() {
    $defaults = [
      'errors' => [],
      'warnings' => [],
      'errors_summary' => NULL,
      'warnings_summary' => NULL,
    ];
    return $this->state->get('auto_updates_test.check_error', []) + $defaults;
  }

  /**
   * {@inheritdoc}
   */
  public function getErrors(): array {
    return $this->getMessages()['errors'];
  }

  /**
   * {@inheritdoc}
   */
  public function getWarnings(): array {
    return $this->getMessages()['warnings'];
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
      'auto_updates_test.check_error',
      [
        'errors' => $errors,
        'warnings' => $warnings,
        'errors_summary' => $errors_summary ? new TranslatableMarkup($errors_summary) : NULL,
        'warnings_summary' => $warnings_summary ? new TranslatableMarkup($warnings_summary) : NULL,
      ]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getErrorsSummary(): ?TranslatableMarkup {
    return $this->getMessages()['errors_summary'];
  }

  /**
   * {@inheritdoc}
   */
  public function getWarningsSummary(): ?TranslatableMarkup {
    return $this->getMessages()['warnings_summary'];
  }

}
