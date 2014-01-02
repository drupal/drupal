<?php

/**
 * @file
 * Contains \Drupal\system\Tests\System\StatusTest.
 */

namespace Drupal\system\Tests\System;

use Drupal\simpletest\WebTestBase;

/**
 * Tests administrative status page.
 */
class StatusTest extends WebTestBase {

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Status page',
      'description' => 'Tests output on the status overview page.',
      'group' => 'System',
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $admin_user = $this->drupalCreateUser(array(
      'administer site configuration',
    ));
    $this->drupalLogin($admin_user);
  }

  /**
   * Tests that the status page returns.
   */
  public function testStatusPage() {
    // Go to Administration.
    $this->drupalGet('admin/reports/status');
    $this->assertResponse(200, 'The status page is reachable.');
  }

}
