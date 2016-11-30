<?php

namespace Drupal\FunctionalTests;

use Drupal\Tests\BrowserTestBase;

/**
 * Test for BrowserTestBase::getTestMethodCaller() in child classes.
 *
 * @group browsertestbase
 */
class GetTestMethodCallerExtendsTest extends GetTestMethodCallerTest {

  /**
   * A test method that is not present in the parent class.
   */
  public function testGetTestMethodCallerChildClass() {
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
