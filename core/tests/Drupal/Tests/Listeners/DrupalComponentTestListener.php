<?php

namespace Drupal\Tests\Listeners;

use Drupal\KernelTests\KernelTestBase;;
use Drupal\Tests\BrowserTestBase;;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\BaseTestListener;

/**
 * Ensures that no component tests are extending a core test base class.
 */
class DrupalComponentTestListener extends BaseTestListener {

  /**
   * {@inheritdoc}
   */
  public function endTest(\PHPUnit_Framework_Test $test, $time) {
    if (substr($test->toString(), 0, 22) == 'Drupal\Tests\Component') {
      if ($test instanceof BrowserTestBase || $test instanceof KernelTestBase || $test instanceof UnitTestCase) {
        $error = new \PHPUnit_Framework_AssertionFailedError('Component tests should not extend a core test base class.');
        $test->getTestResultObject()->addFailure($test, $error, $time);
      }
    }
  }

}
