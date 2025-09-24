<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Core\Test;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests Drupal's extension to manage code deprecation.
 */
#[Group('Test')]
#[IgnoreDeprecations]
#[RunTestsInSeparateProcesses]
class PhpUnitBridgeTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['deprecation_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests deprecation message from deprecation_test_function().
   */
  public function testSilencedError(): void {
    $this->expectDeprecation('This is the deprecation message for deprecation_test_function().');
    $this->assertEquals('known_return_value', deprecation_test_function());
  }

  /**
   * Tests deprecation message from deprecated route.
   */
  public function testErrorOnSiteUnderTest(): void {
    $this->expectDeprecation('This is the deprecation message for deprecation_test_function().');
    $this->drupalGet(Url::fromRoute('deprecation_test.route'));
  }

}
