<?php

namespace Drupal\auto_updates_test2\ReadinessChecker;

use Drupal\auto_updates_test\ReadinessChecker\TestChecker1;

/**
 * A test readiness checker.
 */
class TestChecker2 extends TestChecker1 {

  protected const STATE_KEY = 'auto_updates_test2.checker_results';

}
