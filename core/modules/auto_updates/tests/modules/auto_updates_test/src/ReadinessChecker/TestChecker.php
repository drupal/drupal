<?php

namespace Drupal\auto_updates_test\ReadinessChecker;

use Drupal\auto_updates\ReadinessChecker\ReadinessCheckerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * A test readiness checker.
 */
class TestChecker implements ReadinessCheckerInterface {

  use StringTranslationTrait;

  /**
   * The state key for setting and getting checker message.
   */
  const STATE_KEY = 'auto_updates_test.check_error';

  /**
   * Gets the test messages set in state.
   *
   * @return string[][]
   *   The test messages.
   *
   * @see \Drupal\Tests\auto_updates\Kernel\ReadinessChecker\TestCheckerTrait::setTestMessages()
   */
  private static function getMessages() {
    return \Drupal::state()->get(
        static::STATE_KEY,
        []
      ) + [
        'errors' => [],
        'warnings' => [],
      ];
  }

  /**
   * {@inheritdoc}
   */
  public function getErrors(): array {
    return static::getMessages()['errors'];
  }

  /**
   * {@inheritdoc}
   */
  public function getWarnings(): array {
    return static::getMessages()['warnings'];
  }

}
