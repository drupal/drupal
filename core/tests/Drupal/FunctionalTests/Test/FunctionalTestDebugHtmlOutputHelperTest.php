<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Test;

use Drupal\Tests\BrowserTestBase;

/**
 * Helper test for FunctionalTestDebugHtmlOutputTest.
 *
 * @see \Drupal\FunctionalTests\Test\FunctionalTestDebugHtmlOutputTest::testFunctionalTestDebugHtmlOutput
 *
 * @group browsertestbase
 */
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
  }

}
