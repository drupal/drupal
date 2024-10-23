<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Installer;

/**
 * Verifies that profiles invalid config can not be installed.
 *
 * @group Installer
 */
class InstallerExistingConfigNoConfigTest extends InstallerConfigDirectoryTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  protected $profile = 'no_config_profile';

  /**
   * Final installer step: Configure site.
   */
  protected function setUpSite(): void {
    // There are errors therefore there is nothing to do here.
  }

  /**
   * {@inheritdoc}
   */
  protected function getConfigLocation(): string {
    return __DIR__ . '/../../../fixtures/config_install/testing_config_install_no_config';
  }

  /**
   * Tests that profiles with an empty config/sync directory do not work.
   */
  public function testConfigSync(): void {
    $this->assertSession()->titleEquals('Configuration validation | Drupal');
    $this->assertSession()->pageTextContains('The configuration synchronization failed validation.');
    $this->assertSession()->pageTextContains('This import is empty and if applied would delete all of your configuration, so has been rejected.');

    // Ensure there is no continuation button.
    $this->assertSession()->pageTextNotContains('Save and continue');
    $this->assertSession()->buttonNotExists('edit-submit');
  }

}
