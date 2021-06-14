<?php

namespace Drupal\Tests\Listeners;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\AssertionFailedError;

/**
 * Ensures that no component tests are extending a core test base class.
 *
 * @internal
 */
trait DrupalComponentTestListenerTrait {

  /**
   * Reacts to the end of a test.
   *
   * @param \PHPUnit\Framework\Test $test
   *   The test object that has ended its test run.
   * @param float $time
   *   The time the test took.
   */
  protected function componentEndTest($test, $time) {
    /** @var \PHPUnit\Framework\Test $test */
    if (substr($test->toString(), 0, 22) == 'Drupal\Tests\Component') {
      if ($test instanceof BrowserTestBase || $test instanceof KernelTestBase || $test instanceof UnitTestCase) {
        $error = new AssertionFailedError('Component tests should not extend a core test base class.');
        $test->getTestResultObject()->addFailure($test, $error, $time);
      }
    }
  }

}
