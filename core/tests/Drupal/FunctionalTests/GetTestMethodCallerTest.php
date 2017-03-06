<?php

namespace Drupal\FunctionalTests;

use Drupal\Tests\BrowserTestBase;

/**
 * Explicit test for BrowserTestBase::getTestMethodCaller().
 *
 * @group browsertestbase
 */
class GetTestMethodCallerTest extends BrowserTestBase {

  /**
   * Tests BrowserTestBase::getTestMethodCaller().
   */
  public function testGetTestMethodCaller() {
    $method_caller = $this->getTestMethodCaller();
    $expected = [
      'file' => __FILE__,
      'line' => 18,
      'function' => __CLASS__ . '->' . __FUNCTION__ . '()',
      'class' => BrowserTestBase::class,
      'object' => $this,
      'type' => '->',
      'args' => [],
    ];
    $this->assertEquals($expected, $method_caller);
  }

}
