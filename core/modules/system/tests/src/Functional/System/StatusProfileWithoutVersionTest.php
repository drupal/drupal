<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\System;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the Status Report page if the installation profile has no version.
 *
 * @group system
 */
class StatusProfileWithoutVersionTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'testing_no_version';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $admin_user = $this->drupalCreateUser([
      'administer site configuration',
      'access site reports',
    ]);
    $this->drupalLogin($admin_user);
  }

  /**
   * Tests that an installation profile that has no version is displayed.
   *
   * @group legacy
   */
  public function testStatusPage(): void {
    $this->drupalGet('admin/reports/status');
    $this->assertSession()->statusCodeEquals(200);

    // Check that the installation profile information is displayed.
    $this->assertSession()->pageTextContains('Testing - No Version (testing_no_version)');
  }

}
