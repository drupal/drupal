<?php

namespace Drupal\KernelTests\Core\Extension;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests deprecated update.inc functions.
 *
 * @group legacy
 * @group extension
 */
class UpdateDeprecationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Include the legacy update.inc file.
    include_once $this->root . '/core/includes/update.inc';
  }

  /**
   * Tests update_check_incompatibility() function.
   */
  public function testUpdateCheckIncompatibility() {
    $this->expectDeprecation('update_check_incompatibility() is deprecated in drupal:9.1.0 and is removed from drupal:10.0.0. No direct replacement is provided. See https://www.drupal.org/node/3150727');
    $this->assertTrue(update_check_incompatibility('incompatible_module'));
    $this->assertFalse(update_check_incompatibility('system'));
  }

}
