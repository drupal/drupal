<?php

declare(strict_types=1);

namespace Drupal\Tests\views_ui\Functional;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests existence of the views plugin report.
 */
#[Group('views_ui')]
#[RunTestsInSeparateProcesses]
class ReportTest extends UITestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['views', 'views_ui'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Stores an admin user used by the different tests.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;

  /**
   * Tests the existence of the views plugin report.
   */
  public function testReport(): void {
    $this->drupalLogin($this->adminUser);

    // Test the report page.
    $this->drupalGet('admin/reports/views-plugins');
    $this->assertSession()->statusCodeEquals(200);
  }

}
