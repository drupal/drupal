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
   * @return string[][]
   *   The test messages.
   *
   * @see \Drupal\Tests\auto_updates\Kernel\ReadinessChecker\TestCheckerTrait::setTestMessages()
   */
  protected function getMessages() {
    $defaults = ['errors' => [], 'warnings' => []];
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
   */
  public static function setTestMessages(array $errors = [], array $warnings = []): void {
    \Drupal::state()->set(
      'auto_updates_test.check_error',
      [
        'errors' => $errors,
        'warnings' => $warnings,
      ]
    );
  }

  public function getErrorsSummary(): ?TranslatableMarkup {
    return NULL;// TODO: Implement getErrorsSummary() method.
  }

  public function getWarningsSummary(): ?TranslatableMarkup {
    return null;// TODO: Implement getWarningsSummary() method.
  }
}
