<?php

namespace Drupal\auto_updates_test\ReadinessChecker;

use Drupal\auto_updates\ReadinessChecker\ReadinessCheckerInterface;
use Drupal\auto_updates\ReadinessChecker\ReadinessCheckerResult;
use Drupal\Core\State\StateInterface;

/**
 * A test readiness checker.
 */
class TestChecker implements ReadinessCheckerInterface {

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
   * @param \Drupal\auto_updates\ReadinessChecker\ReadinessCheckerResult $checker_result
   *   The test checker result.
   */
  public static function setTestResult(ReadinessCheckerResult $checker_result): void {
    \Drupal::state()->set('auto_updates_test.checker_results', $checker_result);
  }

  /**
   * {@inheritdoc}
   */
  public function getResult(): ?ReadinessCheckerResult {
    return $this->state->get('auto_updates_test.checker_results', NULL);
  }

}
