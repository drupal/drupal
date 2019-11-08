<?php

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
  public static $modules = ['views', 'views_ui'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Stores an admin user used by the different tests.
   *
   * @var \Drupal\user\User
   */
  protected $adminUser;

  /**
   * Tests the existence of the views plugin report.
   */
  public function testReport() {
    $this->drupalLogin($this->adminUser);

    // Test the report page.
    $this->drupalGet('admin/reports/views-plugins');
    $this->assertResponse(200, "Views report page exists");
  }

}
