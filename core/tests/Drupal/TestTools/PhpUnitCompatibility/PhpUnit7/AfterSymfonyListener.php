<?php

namespace Drupal\TestTools\PhpUnitCompatibility\PhpUnit7;

use PHPUnit\Framework\Test;
use PHPUnit\Framework\TestListener;
use PHPUnit\Framework\TestListenerDefaultImplementation;

/**
 * Listens to PHPUnit test runs.
 *
 * @internal
 */
class AfterSymfonyListener implements TestListener {
  use TestListenerDefaultImplementation;

  /**
   * {@inheritdoc}
   */
  public function endTest(Test $test, float $time): void {
    restore_error_handler();
  }

}
