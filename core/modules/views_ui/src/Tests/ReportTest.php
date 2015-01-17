<?php

/**
 * @file
 * Contains \Drupal\views_ui\Tests\ReportTest.
 */

namespace Drupal\views_ui\Tests;
use Drupal\simpletest\WebTestBase;

/**
 * Tests existence of the views plugin report.
 *
 * @group views_ui
 */
class ReportTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('views', 'views_ui');

  /**
   * Stores an admin user used by the different tests.
   *
   * @var \Drupal\user\User
   */
  protected $adminUser;

  protected function setUp() {
    parent::setUp();
    $this->adminUser = $this->drupalCreateUser(array('administer views'));
  }

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
