<?php

namespace Drupal\system\Tests\Installer;

use Drupal\simpletest\InstallerTestBase;

/**
 * Tests the installer with an existing Drupal installation.
 *
 * @group Installer
 */
class InstallerExistingInstallationTest extends InstallerTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
  }

  /**
   * Verifies that Drupal can't be reinstalled while an existing installation is
   * available.
   */
  public function testInstaller() {
    // Verify that Drupal can't be immediately reinstalled.
    $this->visitInstaller();
    $this->assertRaw('Drupal already installed');

    // Delete settings.php and attempt to reinstall again.
    unlink($this->siteDirectory . '/settings.php');
    $this->visitInstaller();
    $this->setUpLanguage();
    $this->setUpProfile();
    $this->setUpSettings();
    $this->assertRaw('Drupal already installed');
  }

}
