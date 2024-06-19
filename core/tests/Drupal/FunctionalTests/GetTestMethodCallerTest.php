<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests;

use Drupal\Tests\BrowserTestBase;

/**
 * Explicit test for BrowserTestBase::getTestMethodCaller().
 *
 * @group browsertestbase
 */
class GetTestMethodCallerTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests BrowserTestBase::getTestMethodCaller().
   */
  public function testGetTestMethodCaller(): void {
    $method_caller = $this->getTestMethodCaller();
    $expected = [
      'file' => __FILE__,
      'line' => 25,
      'function' => __CLASS__ . '->' . __FUNCTION__ . '()',
      'class' => BrowserTestBase::class,
      'object' => $this,
      'type' => '->',
      'args' => [],
    ];
    $this->assertEquals($expected, $method_caller);
  }

}
