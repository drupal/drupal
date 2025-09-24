<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Test;

use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Helper test for FunctionalTestDebugHtmlOutputTest.
 *
 * @see \Drupal\FunctionalTests\Test\FunctionalTestDebugHtmlOutputTest::testFunctionalTestDebugHtmlOutput
 */
#[Group('browsertestbase')]
#[RunTestsInSeparateProcesses]
class FunctionalTestDebugHtmlOutputHelperTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Creates one page of debug HTML output.
   */
  public function testCreateFunctionalTestDebugHtmlOutput(): void {
    $this->drupalGet('<front>');
    $this->assertSession()->statusCodeEquals(200);
  }

}
