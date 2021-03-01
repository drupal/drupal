<?php

namespace Drupal\auto_updates_test\ReadinessChecker;

use Drupal\auto_updates\ReadinessChecker\ReadinessCheckerInterface;
use Drupal\Core\State\StateInterface;

/**
 * A test readiness checker.
 */
class TestChecker1 implements ReadinessCheckerInterface {

  /**
   * The key to use store the test results.
   */
  protected const STATE_KEY = 'auto_updates_test.checker_results';

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
   * Sets messages for this readiness checker.
   *
   * This method is static to enable setting the expected messages before the
   * test module is enabled.
   *
   * @param \Drupal\auto_updates\ReadinessChecker\ReadinessCheckerResult[] $checker_results
   *   The test checker result.
   */
  public static function setTestResult(array $checker_results): void {
    \Drupal::state()->set(static::STATE_KEY, $checker_results);
  }

  /**
   * {@inheritdoc}
   */
  public function getResults(): array {
    return $this->state->get(static::STATE_KEY, []);
  }

}
