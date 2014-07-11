<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Installer\InstallerEmptySettingsTest.
 */

namespace Drupal\system\Tests\Installer;

use Drupal\simpletest\InstallerTestBase;

/**
 * Tests the installer with empty settings file.
 *
 * @group Installer
 */
class InstallerEmptySettingsTest extends InstallerTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    // Create an empty settings.php file.
    touch($this->siteDirectory . '/settings.php');
    parent::setUp();
  }

  /**
   * Verifies that installation succeeded.
   */
  public function testInstaller() {
    $this->assertUrl('user/1');
    $this->assertResponse(200);
  }

}
