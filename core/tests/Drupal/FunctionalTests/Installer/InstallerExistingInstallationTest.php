<?php

namespace Drupal\FunctionalTests\Installer;

/**
 * Tests the installer with an existing Drupal installation.
 *
 * @group Installer
 */
class InstallerExistingInstallationTest extends InstallerTestBase {

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
    $this->setUpRequirementsProblem();
    $this->setUpSettings();
    $this->assertRaw('Drupal already installed');
  }

}
