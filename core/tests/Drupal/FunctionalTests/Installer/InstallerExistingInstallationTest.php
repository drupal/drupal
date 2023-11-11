<?php

namespace Drupal\FunctionalTests\Installer;

/**
 * Tests the installer with an existing Drupal installation.
 *
 * @group Installer
 */
class InstallerExistingInstallationTest extends InstallerTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests that Drupal fails to install when there is an existing installation.
   */
  public function testInstaller() {
    // Verify that Drupal can't be immediately reinstalled.
    $this->visitInstaller();
    $this->assertSession()->pageTextContains('Drupal already installed');

    // Verify that Drupal version is not displayed.
    $this->assertSession()->pageTextNotContains(\Drupal::VERSION);

    // Delete settings.php and attempt to reinstall again.
    unlink($this->siteDirectory . '/settings.php');
    $this->visitInstaller();
    $this->setUpLanguage();
    $this->setUpProfile();
    $this->setUpRequirementsProblem();
    $this->setUpSettings();
    $this->assertSession()->pageTextContains('Drupal already installed');
  }

}
