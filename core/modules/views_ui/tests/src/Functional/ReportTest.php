<?php

declare(strict_types=1);

namespace Drupal\Tests\views_ui\Functional;

/**
 * Tests existence of the views plugin report.
 *
 * @group views_ui
 */
class ReportTest extends UITestBase {

  /**
   * Modules to enable.
   *
   * @var array
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
